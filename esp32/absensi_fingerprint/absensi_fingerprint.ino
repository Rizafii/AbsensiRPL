#include <WiFi.h>
#include <HTTPClient.h>
#include <Adafruit_Fingerprint.h>
#include <SPI.h>
#include <LovyanGFX.hpp>

// Hardware configuration (fixed, do not change)
#define FP_RX_PIN 21
#define FP_TX_PIN 22
#define FP_BAUDRATE 57600
#define TOUCH_CS_PIN 13
#define TFT_MISO_PIN 19
#define TFT_MOSI_PIN 23
#define TFT_SCLK_PIN 18
#define TFT_CS_PIN 15
#define TFT_DC_PIN 2
#define TFT_RST_PIN 4

#if defined(VSPI_HOST)
#define LGFX_SPI_HOST VSPI_HOST
#elif defined(SPI2_HOST)
#define LGFX_SPI_HOST SPI2_HOST
#else
#error "No suitable SPI host found for LovyanGFX on this target"
#endif

#define LCD_WIDTH 480
#define LCD_HEIGHT 320

class LGFX : public lgfx::LGFX_Device
{
    lgfx::Panel_ILI9488 _panel;
    lgfx::Bus_SPI _bus;

public:
    LGFX()
    {
        {
            auto cfg = _bus.config();
            cfg.spi_host = LGFX_SPI_HOST;
            cfg.spi_mode = 0;
            cfg.freq_write = 40000000;
            cfg.freq_read = 16000000;
            cfg.spi_3wire = false;
            cfg.use_lock = true;
            cfg.dma_channel = 1;
            cfg.pin_sclk = TFT_SCLK_PIN;
            cfg.pin_mosi = TFT_MOSI_PIN;
            cfg.pin_miso = TFT_MISO_PIN;
            cfg.pin_dc = TFT_DC_PIN;
            _bus.config(cfg);
            _panel.setBus(&_bus);
        }

        {
            auto cfg = _panel.config();
            cfg.pin_cs = TFT_CS_PIN;
            cfg.pin_rst = TFT_RST_PIN;
            cfg.pin_busy = -1;

            cfg.memory_width = LCD_HEIGHT;
            cfg.memory_height = LCD_WIDTH;
            cfg.panel_width = LCD_HEIGHT;
            cfg.panel_height = LCD_WIDTH;
            cfg.offset_x = 0;
            cfg.offset_y = 0;
            cfg.offset_rotation = 0;

            cfg.dummy_read_pixel = 8;
            cfg.dummy_read_bits = 1;
            cfg.readable = false;
            cfg.invert = false;
            cfg.rgb_order = false;
            cfg.dlen_16bit = false;
            cfg.bus_shared = true;

            _panel.config(cfg);
        }

        setPanel(&_panel);
    }
};

const char* WIFI_SSID = "GURU-SMKN6";
const char* WIFI_PASSWORD = "cerdasbergerak";
const char* API_BASE_URL = "http://10.0.0.54:8000";
const char* API_TOKEN = "jgk0advefk90gj4ngin4290";

HardwareSerial FingerSerial(2);
Adafruit_Fingerprint finger(&FingerSerial);
LGFX tft;

unsigned long lastScanAt = 0;
unsigned long lastEnrollPollAt = 0;

const unsigned long FINGER_SCAN_INTERVAL_MS = 250;
const unsigned long ENROLL_POLL_INTERVAL_MS = 3000;
const unsigned long UI_REFRESH_INTERVAL_MS = 1000;

int lcdWidth = LCD_WIDTH;
int lcdHeight = LCD_HEIGHT;

String lcdModeText = "";
String lcdWifiText = "";
int lcdLastFingerprintId = -1;
unsigned long lastUiRefreshAt = 0;

void initDisplay();
void drawLcdLayout();
void refreshLcdStatus();
void setModeOnLcd(const String& mode);
void updateWifiOnLcd();
void updateLastFingerprintOnLcd(int fingerprintId);
void showAlertOnLcd(const String& title, const String& details, uint16_t color);
void splitToTwoLines(const String& text, String& line1, String& line2);
String extractJsonString(const String& json, const String& key, int fromIndex = 0);

bool connectWiFi();
void ensureWiFiConnected();
bool initFingerprintSensor();
int getFingerprintID();
void postAttendance(int userId);
void pollEnrollRequest();
int extractFingerprintId(const String& json);
bool getFingerprintEnroll(int id);
void postEnrollDone(int fingerprintId, const char* status);

void setup()
{
    Serial.begin(115200);

    initDisplay();
    setModeOnLcd("BOOTING");
    showAlertOnLcd("System", "Inisialisasi perangkat...", TFT_CYAN);

    bool wifiReady = connectWiFi();
    bool fingerprintReady = initFingerprintSensor();

    if (wifiReady && fingerprintReady) {
        setModeOnLcd("IDLE");
        showAlertOnLcd("Siap", "Tempel sidik jari untuk absensi", TFT_GREEN);
    } else if (!wifiReady && fingerprintReady) {
        setModeOnLcd("WIFI OFFLINE");
        showAlertOnLcd("Mode Offline", "WiFi gagal, sistem akan coba reconnect", TFT_ORANGE);
    } else if (wifiReady && !fingerprintReady) {
        setModeOnLcd("FINGER ERROR");
        showAlertOnLcd("Sensor Error", "Fingerprint tidak terdeteksi", TFT_RED);
    } else {
        setModeOnLcd("ERROR");
        showAlertOnLcd("Init Gagal", "WiFi dan sensor fingerprint bermasalah", TFT_RED);
    }
}

void loop()
{
    ensureWiFiConnected();
    refreshLcdStatus();

    if (millis() - lastScanAt >= FINGER_SCAN_INTERVAL_MS) {
        lastScanAt = millis();

        int fingerprintId = getFingerprintID();

        if (fingerprintId > 0) {
            updateLastFingerprintOnLcd(fingerprintId);
            postAttendance(fingerprintId);
        }
    }

    if (millis() - lastEnrollPollAt >= ENROLL_POLL_INTERVAL_MS) {
        lastEnrollPollAt = millis();
        pollEnrollRequest();
    }
}

void initDisplay()
{
    tft.begin();
    tft.setColorDepth(16);
    tft.setRotation(1); // 480x320 landscape

    // Quick boot pattern for visual validation that LCD pipeline is alive.
    tft.fillScreen(TFT_RED);
    delay(120);
    tft.fillScreen(TFT_GREEN);
    delay(120);
    tft.fillScreen(TFT_BLUE);
    delay(120);

    lcdWidth = tft.width();
    lcdHeight = tft.height();

    drawLcdLayout();
    setModeOnLcd("INIT");
    updateWifiOnLcd();
    updateLastFingerprintOnLcd(-1);
}

void drawLcdLayout()
{
    tft.fillScreen(TFT_BLACK);

    tft.fillRect(0, 0, lcdWidth, 34, TFT_NAVY);
    tft.setTextSize(2);
    tft.setTextColor(TFT_WHITE, TFT_NAVY);
    tft.setCursor(10, 9);
    tft.print("Sistem Absensi IoT");

    tft.drawRoundRect(8, 42, lcdWidth - 16, 80, 6, TFT_DARKGREY);
    tft.drawRoundRect(8, 130, lcdWidth - 16, lcdHeight - 138, 6, TFT_DARKGREY);

    tft.setTextSize(2);
    tft.setTextColor(TFT_WHITE, TFT_BLACK);
    tft.setCursor(18, 52);
    tft.print("WiFi:");
    tft.setCursor(18, 76);
    tft.print("Mode:");
    tft.setCursor(18, 100);
    tft.print("Last ID:");
}

void refreshLcdStatus()
{
    if (millis() - lastUiRefreshAt < UI_REFRESH_INTERVAL_MS) {
        return;
    }

    lastUiRefreshAt = millis();
    updateWifiOnLcd();
}

void setModeOnLcd(const String& mode)
{
    if (mode == lcdModeText) {
        return;
    }

    lcdModeText = mode;
    tft.fillRect(96, 76, lcdWidth - 110, 18, TFT_BLACK);
    tft.setTextSize(2);
    tft.setTextColor(TFT_YELLOW, TFT_BLACK);
    tft.setCursor(96, 76);
    tft.print(mode);
}

void updateWifiOnLcd()
{
    String wifiText;
    uint16_t wifiColor = TFT_ORANGE;

    if (WiFi.status() == WL_CONNECTED) {
        wifiText = String("Connected ") + WiFi.localIP().toString();
        wifiColor = TFT_GREEN;
    } else {
        wifiText = "Disconnected";
    }

    if (wifiText == lcdWifiText) {
        return;
    }

    lcdWifiText = wifiText;
    tft.fillRect(96, 52, lcdWidth - 110, 18, TFT_BLACK);
    tft.setTextSize(2);
    tft.setTextColor(wifiColor, TFT_BLACK);
    tft.setCursor(96, 52);
    tft.print(wifiText);
}

void updateLastFingerprintOnLcd(int fingerprintId)
{
    if (fingerprintId == lcdLastFingerprintId) {
        return;
    }

    lcdLastFingerprintId = fingerprintId;

    tft.fillRect(120, 100, lcdWidth - 134, 18, TFT_BLACK);
    tft.setTextSize(2);
    tft.setTextColor(TFT_CYAN, TFT_BLACK);
    tft.setCursor(120, 100);

    if (fingerprintId > 0) {
        tft.print(fingerprintId);
    } else {
        tft.print("-");
    }
}

void splitToTwoLines(const String& text, String& line1, String& line2)
{
    const int maxCharsPerLine = 34;

    if (text.length() <= maxCharsPerLine) {
        line1 = text;
        line2 = "";
        return;
    }

    int split = text.lastIndexOf(' ', maxCharsPerLine);

    if (split < 0) {
        split = maxCharsPerLine;
    }

    line1 = text.substring(0, split);

    String remaining = text.substring(split);
    remaining.trim();

    if (remaining.length() > maxCharsPerLine) {
        line2 = remaining.substring(0, maxCharsPerLine - 3) + "...";
    } else {
        line2 = remaining;
    }
}

void showAlertOnLcd(const String& title, const String& details, uint16_t color)
{
    const int x = 12;
    const int y = 134;
    const int w = lcdWidth - 24;
    const int h = lcdHeight - 146;

    tft.fillRect(x, y, w, h, TFT_BLACK);
    tft.drawRoundRect(x - 2, y - 2, w + 4, h + 4, 6, color);

    String line1;
    String line2;
    splitToTwoLines(details, line1, line2);

    tft.setTextSize(2);
    tft.setTextColor(color, TFT_BLACK);
    tft.setCursor(x + 6, y + 6);
    tft.print(title);

    tft.setTextColor(TFT_WHITE, TFT_BLACK);
    tft.setCursor(x + 6, y + 34);
    tft.print(line1);

    if (line2.length() > 0) {
        tft.setCursor(x + 6, y + 58);
        tft.print(line2);
    }
}

String extractJsonString(const String& json, const String& key, int fromIndex)
{
    String token = String("\"") + key + "\":";
    int keyIndex = json.indexOf(token, fromIndex);

    if (keyIndex < 0) {
        return "";
    }

    int valueStart = keyIndex + token.length();

    while (valueStart < json.length() &&
           (json[valueStart] == ' ' || json[valueStart] == '\n' || json[valueStart] == '\r' || json[valueStart] == '\t')) {
        valueStart++;
    }

    if (valueStart >= json.length()) {
        return "";
    }

    if (json[valueStart] == '"') {
        valueStart++;
        int valueEnd = valueStart;

        while (valueEnd < json.length()) {
            if (json[valueEnd] == '"' && (valueEnd == valueStart || json[valueEnd - 1] != '\\')) {
                break;
            }
            valueEnd++;
        }

        if (valueEnd <= valueStart) {
            return "";
        }

        return json.substring(valueStart, valueEnd);
    }

    int valueEnd = valueStart;

    while (valueEnd < json.length() &&
           json[valueEnd] != ',' &&
           json[valueEnd] != '}' &&
           json[valueEnd] != '\n' &&
           json[valueEnd] != '\r') {
        valueEnd++;
    }

    return json.substring(valueStart, valueEnd);
}

bool connectWiFi()
{
    setModeOnLcd("WIFI CONNECT");
    showAlertOnLcd("WiFi", String("Menghubungkan ke ") + WIFI_SSID, TFT_CYAN);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    Serial.print("Connecting to WiFi");
    int attempts = 0;

    while (WiFi.status() != WL_CONNECTED && attempts < 60) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.print("WiFi connected: ");
        Serial.println(WiFi.localIP());

        updateWifiOnLcd();
        showAlertOnLcd("WiFi Terhubung", WiFi.localIP().toString(), TFT_GREEN);
        return true;
    } else {
        Serial.println("WiFi connection failed");
        updateWifiOnLcd();
        showAlertOnLcd("WiFi Gagal", "Periksa SSID/password atau sinyal", TFT_RED);
        return false;
    }
}

void ensureWiFiConnected()
{
    if (WiFi.status() == WL_CONNECTED) {
        return;
    }

    setModeOnLcd("WIFI RETRY");
    connectWiFi();

    if (WiFi.status() == WL_CONNECTED) {
        setModeOnLcd("IDLE");
    }
}

bool initFingerprintSensor()
{
    FingerSerial.begin(FP_BAUDRATE, SERIAL_8N1, FP_RX_PIN, FP_TX_PIN);
    finger.begin(FP_BAUDRATE);

    if (finger.verifyPassword()) {
        Serial.println("Fingerprint sensor detected");
        showAlertOnLcd("Fingerprint OK", "Sensor terdeteksi dan siap", TFT_GREEN);
        return true;
    } else {
        Serial.println("Fingerprint sensor not detected");
        showAlertOnLcd("Fingerprint Error", "Sensor tidak terdeteksi", TFT_RED);
        return false;
    }
}

int getFingerprintID()
{
    uint8_t p = finger.getImage();

    if (p != FINGERPRINT_OK) {
        return -1;
    }

    p = finger.image2Tz();

    if (p != FINGERPRINT_OK) {
        return -1;
    }

    p = finger.fingerFastSearch();

    if (p != FINGERPRINT_OK) {
        return -1;
    }

    Serial.print("Fingerprint matched ID: ");
    Serial.println(finger.fingerID);

    return finger.fingerID;
}

void postAttendance(int userId)
{
    if (WiFi.status() != WL_CONNECTED) {
        setModeOnLcd("WIFI OFFLINE");
        showAlertOnLcd("Absensi Gagal", "WiFi tidak terhubung", TFT_RED);
        return;
    }

    setModeOnLcd("KIRIM ABSENSI");
    showAlertOnLcd("Absensi", String("Mengirim ID ") + userId + " ke server", TFT_CYAN);

    HTTPClient http;
    String url = String(API_BASE_URL) + "/api/attendance";

    http.begin(url);
    http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
    http.addHeader("Content-Type", "application/json");

    String body = String("{\"user_id\":") + userId + "}";
    int httpCode = http.POST(body);
    String response = http.getString();

    Serial.print("POST /api/attendance code: ");
    Serial.println(httpCode);
    Serial.println(response);

    http.end();

    if (httpCode <= 0) {
        showAlertOnLcd("Absensi Gagal", "Tidak bisa menghubungi server", TFT_RED);
        setModeOnLcd("IDLE");
        return;
    }

    String topStatus = extractJsonString(response, "status");
    String message = extractJsonString(response, "message");
    int userObjectIndex = response.indexOf("\"user\":");
    String userName = userObjectIndex >= 0 ? extractJsonString(response, "name", userObjectIndex) : "";
    int dataObjectIndex = response.indexOf("\"data\":");
    String attendanceStatus = dataObjectIndex >= 0 ? extractJsonString(response, "status", dataObjectIndex) : "";

    if (topStatus == "success") {
        String detail = message;

        if (userName.length() > 0) {
            detail = userName + " | " + detail;
        }

        if (attendanceStatus.length() > 0) {
            detail += " (" + attendanceStatus + ")";
        }

        showAlertOnLcd("Absensi Berhasil", detail, TFT_GREEN);
    } else {
        if (message.length() == 0) {
            message = "Format response tidak valid";
        }

        showAlertOnLcd("Absensi Gagal", message, TFT_RED);
    }

    setModeOnLcd("IDLE");
}

void pollEnrollRequest()
{
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }

    HTTPClient http;
    String url = String(API_BASE_URL) + "/api/enroll/latest";

    http.begin(url);
    http.addHeader("Authorization", String("Bearer ") + API_TOKEN);

    int httpCode = http.GET();
    String response = http.getString();

    http.end();

    if (httpCode != 200) {
        Serial.print("GET /api/enroll/latest failed: ");
        Serial.println(httpCode);
        return;
    }

    Serial.print("Enroll poll response: ");
    Serial.println(response);

    bool isPending = response.indexOf("\"status\":\"pending\"") >= 0 || response.indexOf("\"status\": \"pending\"") >= 0;

    if (!isPending) {
        return;
    }

    int fingerprintId = extractFingerprintId(response);

    if (fingerprintId <= 0) {
        return;
    }

    setModeOnLcd("ENROLL MODE");
    showAlertOnLcd("Enroll Pending", String("Menjalankan enroll ID ") + fingerprintId, TFT_YELLOW);

    bool enrollSuccess = getFingerprintEnroll(fingerprintId);
    postEnrollDone(fingerprintId, enrollSuccess ? "success" : "failed");
    setModeOnLcd("IDLE");
}

int extractFingerprintId(const String& json)
{
    int keyIndex = json.indexOf("\"fingerprint_id\":");

    if (keyIndex < 0) {
        return -1;
    }

    int valueStart = keyIndex + 17;

    while (valueStart < json.length() && (json[valueStart] == ' ' || json[valueStart] == '"')) {
        valueStart++;
    }

    int valueEnd = valueStart;

    while (valueEnd < json.length() && isDigit(json[valueEnd])) {
        valueEnd++;
    }

    if (valueEnd <= valueStart) {
        return -1;
    }

    return json.substring(valueStart, valueEnd).toInt();
}

bool getFingerprintEnroll(int id)
{
    int p = -1;

    Serial.print("Enroll start for ID ");
    Serial.println(id);
    showAlertOnLcd("Enroll", String("ID ") + id + " tempel jari pertama", TFT_CYAN);

    while (p != FINGERPRINT_OK) {
        p = finger.getImage();

        if (p == FINGERPRINT_NOFINGER) {
            delay(50);
        }
    }

    p = finger.image2Tz(1);

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Gagal membaca jari pertama", TFT_RED);
        return false;
    }

    Serial.println("Remove finger");
    showAlertOnLcd("Enroll", "Lepas jari dari sensor", TFT_CYAN);
    delay(2000);

    p = 0;

    while (p != FINGERPRINT_NOFINGER) {
        p = finger.getImage();
        delay(50);
    }

    Serial.println("Place same finger again");
    showAlertOnLcd("Enroll", "Tempel jari yang sama lagi", TFT_CYAN);
    p = -1;

    while (p != FINGERPRINT_OK) {
        p = finger.getImage();

        if (p == FINGERPRINT_NOFINGER) {
            delay(50);
        }
    }

    p = finger.image2Tz(2);

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Gagal membaca jari kedua", TFT_RED);
        return false;
    }

    p = finger.createModel();

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Sidik jari tidak cocok", TFT_RED);
        return false;
    }

    p = finger.storeModel(id);

    if (p == FINGERPRINT_OK) {
        Serial.println("Enroll done");
        showAlertOnLcd("Enroll Berhasil", String("ID ") + id + " tersimpan", TFT_GREEN);
        return true;
    }

    showAlertOnLcd("Enroll Gagal", "Tidak dapat menyimpan ke sensor", TFT_RED);
    return false;
}

void postEnrollDone(int fingerprintId, const char* status)
{
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }

    HTTPClient http;
    String url = String(API_BASE_URL) + "/api/enroll/done";

    http.begin(url);
    http.addHeader("Authorization", String("Bearer ") + API_TOKEN);
    http.addHeader("Content-Type", "application/json");

    String body = String("{\"fingerprint_id\":") + fingerprintId + ",\"status\":\"" + status + "\"}";
    int httpCode = http.POST(body);
    String response = http.getString();

    Serial.print("POST /api/enroll/done code: ");
    Serial.println(httpCode);
    Serial.println(response);

    http.end();

    if (httpCode <= 0) {
        showAlertOnLcd("Sync Enroll Gagal", "Status enroll tidak terkirim", TFT_RED);
        return;
    }

    String topStatus = extractJsonString(response, "status");
    String message = extractJsonString(response, "message");

    if (topStatus == "success") {
        if (message.length() == 0) {
            message = String("ID ") + fingerprintId + " status " + status + " terkirim";
        }

        showAlertOnLcd("Sync Enroll", message, TFT_GREEN);
    } else {
        if (message.length() == 0) {
            message = "Server menolak update enroll";
        }

        showAlertOnLcd("Sync Enroll Gagal", message, TFT_RED);
    }
}
