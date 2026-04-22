#include <WiFi.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include <Adafruit_Fingerprint.h>
#include <SPI.h>
#include <TFT_eSPI.h>
#include <time.h>

// Hardware configuration (fixed, do not change)
#define FP_RX_PIN 21
#define FP_TX_PIN 22
#define FP_BAUDRATE 57600
#define TOUCHSCREEN_CS_PIN 13
#define TFT_BACKLIGHT_ON HIGH

#define LCD_WIDTH 480
#define LCD_HEIGHT 320

const char* DEFAULT_WIFI_SSID = "RPL";
const char* DEFAULT_WIFI_PASSWORD = "rplviska6";
const char* DEFAULT_API_BASE_URL = "http://192.168.13.3:8000";
const char* DEFAULT_API_TOKEN = "jgk0advefk90gj4ngin4290";

const unsigned long FINGER_SCAN_INTERVAL_MS = 250;
const unsigned long ENROLL_POLL_INTERVAL_MS = 3000;
const unsigned long UI_REFRESH_INTERVAL_MS = 1000;
const unsigned long WIFI_RETRY_INTERVAL_MS = 10000;
const unsigned long ENDPOINT_PROBE_INTERVAL_MS = 8000;
const unsigned long TOUCH_HOLD_TO_SETUP_MS = 1200;
const unsigned long CLOCK_SYNC_RETRY_INTERVAL_MS = 30000;

const char* NTP_SERVER = "pool.ntp.org";
const char* CLOCK_TIMEZONE = "WIB-7";

const uint16_t TOUCH_RAW_MIN = 200;
const uint16_t TOUCH_RAW_MAX = 3800;
const uint16_t TOUCH_RAW_Z_MIN = 200;

const uint16_t UI_BG = 0x10A2;
const uint16_t UI_CARD = 0x18E3;
const uint16_t UI_CARD_BORDER = 0x3186;
const uint16_t UI_HEADER = 0x01CF;
const uint16_t UI_ACCENT = 0x04FF;
const uint16_t UI_OK = TFT_GREEN;
const uint16_t UI_WARN = TFT_ORANGE;
const uint16_t UI_ERROR = TFT_RED;

const int SETUP_FIELD_COUNT = 4;
const int SETUP_BUTTON_COUNT = 8;
const int SETUP_KEY_ROWS = 4;
const int SETUP_KEY_COLS = 10;
const char* SETUP_KEYBOARD_LAYOUT[SETUP_KEY_ROWS] = {
    "1234567890",
    "qwertyuiop",
    "asdfghjkl:",
    "zxcvbnm/.-"
};

struct SetupButton {
    int x;
    int y;
    int w;
    int h;
    const char* label;
};

struct DeviceConfig {
    String wifiSsid;
    String wifiPassword;
    String apiBaseUrl;
    String apiToken;
};

HardwareSerial FingerSerial(2);
Adafruit_Fingerprint finger(&FingerSerial);
TFT_eSPI tft = TFT_eSPI();
Preferences preferences;

DeviceConfig deviceConfig;

unsigned long lastScanAt = 0;
unsigned long lastEnrollPollAt = 0;
unsigned long lastUiRefreshAt = 0;
unsigned long lastWiFiRetryAt = 0;
unsigned long lastEndpointProbeAt = 0;
unsigned long lastClockSyncAttemptAt = 0;
unsigned long setupButtonTouchStartAt = 0;
unsigned long lastSetupTriggerAt = 0;

bool setupModeActive = false;
bool setupButtonPressedVisual = false;
bool setupEditorTouchLatched = false;
bool setupKeyboardUppercase = false;
bool clockServiceInitialized = false;
bool clockSynced = false;

int setupSelectedFieldIndex = 0;

String setupEditWifiSsid = "";
String setupEditWifiPassword = "";
String setupEditApiBaseUrl = "";
String setupEditApiToken = "";

String* setupFieldValues[SETUP_FIELD_COUNT] = { nullptr, nullptr, nullptr, nullptr };
const char* setupFieldLabels[SETUP_FIELD_COUNT] = {
    "WiFi SSID",
    "WiFi Password",
    "Endpoint Base URL",
    "API Bearer Token"
};
const int setupFieldMaxLength[SETUP_FIELD_COUNT] = { 32, 64, 120, 120 };
SetupButton setupButtons[SETUP_BUTTON_COUNT];
SetupButton setupKeyButtons[SETUP_KEY_ROWS * SETUP_KEY_COLS];

String setupEditorNotice = "Tap field lalu ketik di keyboard touchscreen.";
uint16_t setupEditorNoticeColor = UI_ACCENT;

int lcdWidth = LCD_WIDTH;
int lcdHeight = LCD_HEIGHT;

int cardWidth = 0;
int wifiCardX = 0;
int endpointCardX = 0;
int topRowY = 0;
int secondRowY = 0;
int cardHeight = 84;
int messagePanelX = 0;
int messagePanelY = 0;
int messagePanelW = 0;
int messagePanelH = 0;
int clockWidgetX = 0;
int clockWidgetY = 0;
int clockWidgetW = 0;
int clockWidgetH = 0;

String lcdModeText = "";
String lcdWifiText = "";
String lcdWifiDetail = "";
String lcdEndpointText = "";
String lcdEndpointDetail = "";
String lcdAlertTitle = "";
String lcdAlertDetail = "";
uint16_t lcdAlertColor = TFT_WHITE;
uint16_t lcdWifiColor = TFT_WHITE;
uint16_t lcdEndpointColor = TFT_WHITE;
String lcdClockText = "";
uint16_t lcdClockColor = TFT_WHITE;
int lcdLastFingerprintId = -1;

String endpointStateText = "UNKNOWN";
String endpointStateDetail = "Belum dicek";
uint16_t endpointStateColor = UI_WARN;

void initDisplay();
void drawDashboardLayout();
void drawCardFrame(int x, int y, int w, int h, const String& title);
void drawConfigButton(bool pressed);
bool isConfigButtonArea(uint16_t x, uint16_t y);
void refreshLcdStatus();
void setModeOnLcd(const String& mode);
void updateWifiOnLcd();
void updateEndpointOnLcd();
void updateLastFingerprintOnLcd(int fingerprintId);
void showAlertOnLcd(const String& title, const String& details, uint16_t color);
void splitToTwoLines(const String& text, String& line1, String& line2);
String shortenText(const String& text, int maxLength);
void updateClockOnLcd();

void loadConfig();
void saveConfig(const DeviceConfig& config);
String normalizeBaseUrl(const String& rawUrl);
String buildApiUrl(const String& path);
String mapAttendanceStatusLabel(const String& status);
void initClockService();
bool syncClockWithNtp(bool forced = false);
String getCurrentClockTimeText();
String getCurrentClockStamp();

bool connectWiFi();
void ensureWiFiConnected();
void probeEndpoint(bool forced = false);
void setEndpointState(const String& state, const String& detail, uint16_t color);
bool readTouchPoint(uint16_t& touchX, uint16_t& touchY);

void handleTouchInput();
void handleSetupEditorTouch();
void startSetupMode();
void stopSetupMode();
void configureSetupButtons();
void configureSetupKeyboardButtons();
void drawSetupEditor();
void drawSetupKeyboard();
void drawSetupFieldRow(int fieldIndex);
void drawSetupEditorNotice(const String& notice, uint16_t color);
int hitTestSetupField(uint16_t x, uint16_t y);
int hitTestSetupButton(uint16_t x, uint16_t y);
int hitTestSetupKey(uint16_t x, uint16_t y);
char resolveSetupKeyChar(int keyIndex);
bool appendCharToActiveSetupField(char character);
void applySetupEditorAction(int actionIndex);
String setupFieldPreviewValue(int fieldIndex, const String& value);
String repeatChar(char fill, int count);
void setSetupEditorNotice(const String& notice, uint16_t color);

bool initFingerprintSensor();
int getFingerprintID();
void postAttendance(int userId);
void pollEnrollRequest();
int extractFingerprintId(const String& json);
bool getFingerprintEnroll(int id);
void postEnrollDone(int fingerprintId, const char* status);
String extractJsonString(const String& json, const String& key, int fromIndex = 0);

void setup()
{
    Serial.begin(115200);
    initClockService();
    loadConfig();
    initDisplay();

    setModeOnLcd("BOOTING");
    showAlertOnLcd("System", "Inisialisasi perangkat... tahan tombol SETUP untuk konfigurasi", UI_ACCENT);

    bool fingerprintReady = initFingerprintSensor();
    bool wifiReady = connectWiFi();

    if (!fingerprintReady) {
        setModeOnLcd("FINGER ERROR");
        showAlertOnLcd("Sensor Error", "Fingerprint tidak terdeteksi", UI_ERROR);
        return;
    }

    if (wifiReady) {
        setModeOnLcd("IDLE");
        showAlertOnLcd("Siap", "Tempel sidik jari untuk absensi", UI_OK);
    } else {
        setModeOnLcd("WIFI OFFLINE");
        showAlertOnLcd("WiFi Offline", "Tahan tombol SETUP untuk ubah WiFi/endpoint", UI_WARN);
    }
}

void loop()
{
    handleTouchInput();

    if (setupModeActive) {
        handleSetupEditorTouch();
        return;
    }

    ensureWiFiConnected();
    syncClockWithNtp(false);
    probeEndpoint();
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
    tft.setRotation(1); // 480x320 landscape

    for (int y = 0; y < LCD_HEIGHT; y += 8) {
        uint8_t r = 6 + (y / 10);
        uint8_t g = 20 + (y / 14);
        uint8_t b = 35 + (y / 9);
        tft.fillRect(0, y, LCD_WIDTH, 8, tft.color565(r, g, b));
    }

    delay(160);

    lcdWidth = tft.width();
    lcdHeight = tft.height();

    drawDashboardLayout();
    setModeOnLcd("INIT");
    updateWifiOnLcd();
    updateEndpointOnLcd();
    updateLastFingerprintOnLcd(-1);
}

void drawDashboardLayout()
{
    tft.fillScreen(UI_BG);

    tft.fillRect(0, 0, lcdWidth, 36, UI_HEADER);
    tft.setTextSize(2);
    tft.setTextColor(TFT_WHITE, UI_HEADER);
    tft.setCursor(10, 10);
    tft.print("Absensi Fingerprint");

    clockWidgetW = 104;
    clockWidgetH = 24;
    clockWidgetX = lcdWidth - 224;
    clockWidgetY = 6;

    tft.fillRoundRect(clockWidgetX, clockWidgetY, clockWidgetW, clockWidgetH, 8, tft.color565(6, 76, 112));
    tft.drawRoundRect(clockWidgetX, clockWidgetY, clockWidgetW, clockWidgetH, 8, TFT_WHITE);

    drawConfigButton(false);

    cardWidth = (lcdWidth - 30) / 2;
    wifiCardX = 10;
    endpointCardX = wifiCardX + cardWidth + 10;
    topRowY = 46;
    secondRowY = topRowY + cardHeight + 10;

    drawCardFrame(wifiCardX, topRowY, cardWidth, cardHeight, "WiFi");
    drawCardFrame(endpointCardX, topRowY, cardWidth, cardHeight, "Endpoint");
    drawCardFrame(wifiCardX, secondRowY, cardWidth, cardHeight, "Mode");
    drawCardFrame(endpointCardX, secondRowY, cardWidth, cardHeight, "Last Finger ID");

    messagePanelX = 10;
    messagePanelY = secondRowY + cardHeight + 10;
    messagePanelW = lcdWidth - 20;
    messagePanelH = lcdHeight - messagePanelY - 10;

    tft.fillRoundRect(messagePanelX, messagePanelY, messagePanelW, messagePanelH, 10, UI_CARD);
    tft.drawRoundRect(messagePanelX, messagePanelY, messagePanelW, messagePanelH, 10, UI_CARD_BORDER);

    tft.setTextSize(1);
    tft.setTextColor(UI_ACCENT, UI_CARD);
    tft.setCursor(messagePanelX + 12, messagePanelY + 8);
    tft.print("Live Notification");

    lcdClockText = "";
    updateClockOnLcd();
}

void drawCardFrame(int x, int y, int w, int h, const String& title)
{
    tft.fillRoundRect(x, y, w, h, 10, UI_CARD);
    tft.drawRoundRect(x, y, w, h, 10, UI_CARD_BORDER);

    tft.setTextSize(1);
    tft.setTextColor(UI_ACCENT, UI_CARD);
    tft.setCursor(x + 12, y + 8);
    tft.print(title);
}

void drawConfigButton(bool pressed)
{
    const int w = 100;
    const int h = 24;
    const int x = lcdWidth - w - 10;
    const int y = 6;

    uint16_t bgColor = pressed ? tft.color565(20, 150, 220) : tft.color565(4, 90, 140);

    tft.fillRoundRect(x, y, w, h, 8, bgColor);
    tft.drawRoundRect(x, y, w, h, 8, TFT_WHITE);

    tft.setTextSize(1);
    tft.setTextColor(TFT_WHITE, bgColor);
    tft.setCursor(x + 18, y + 8);
    tft.print("Tahan SETUP");
}

bool isConfigButtonArea(uint16_t x, uint16_t y)
{
    const int w = 100;
    const int h = 24;
    const int left = lcdWidth - w - 10;
    const int top = 6;
    return x >= left && x <= left + w && y >= top && y <= top + h;
}

void refreshLcdStatus()
{
    if (millis() - lastUiRefreshAt < UI_REFRESH_INTERVAL_MS) {
        return;
    }

    lastUiRefreshAt = millis();
    updateWifiOnLcd();
    updateEndpointOnLcd();
    updateClockOnLcd();
}

void setModeOnLcd(const String& mode)
{
    if (mode == lcdModeText) {
        return;
    }

    lcdModeText = mode;

    int x = wifiCardX + 10;
    int y = secondRowY + 26;
    int w = cardWidth - 20;
    int h = cardHeight - 34;

    tft.fillRect(x, y, w, h, UI_CARD);
    tft.setTextSize(2);
    tft.setTextColor(TFT_YELLOW, UI_CARD);
    tft.setCursor(x + 2, y + 8);
    tft.print(shortenText(mode, 16));
}

void updateWifiOnLcd()
{
    String wifiText;
    String wifiDetail;
    uint16_t wifiColor = UI_WARN;

    if (setupModeActive) {
        wifiText = "SETUP LOCAL";
        wifiDetail = "Edit langsung di layar touch";
        wifiColor = UI_ACCENT;
    } else if (WiFi.status() == WL_CONNECTED) {
        wifiText = "CONNECTED";
        wifiDetail = deviceConfig.wifiSsid + " | " + WiFi.localIP().toString();
        wifiColor = UI_OK;
    } else {
        wifiText = "DISCONNECTED";
        wifiDetail = "SSID: " + deviceConfig.wifiSsid;
        wifiColor = UI_WARN;
    }

    if (wifiText == lcdWifiText && wifiDetail == lcdWifiDetail && wifiColor == lcdWifiColor) {
        return;
    }

    lcdWifiText = wifiText;
    lcdWifiDetail = wifiDetail;
    lcdWifiColor = wifiColor;

    int x = wifiCardX + 10;
    int y = topRowY + 26;
    int w = cardWidth - 20;
    int h = cardHeight - 34;

    tft.fillRect(x, y, w, h, UI_CARD);

    tft.setTextSize(2);
    tft.setTextColor(wifiColor, UI_CARD);
    tft.setCursor(x + 2, y + 4);
    tft.print(wifiText);

    tft.setTextSize(1);
    tft.setTextColor(TFT_WHITE, UI_CARD);
    tft.setCursor(x + 2, y + 30);
    tft.print(shortenText(wifiDetail, 32));
}

void updateEndpointOnLcd()
{
    String endpointText = endpointStateText;
    String endpointDetail = endpointStateDetail;
    uint16_t endpointColor = endpointStateColor;

    if (setupModeActive) {
        endpointText = "CONFIG MODE";
        endpointDetail = "Browser tidak diperlukan";
        endpointColor = UI_ACCENT;
    }

    if (endpointText == lcdEndpointText && endpointDetail == lcdEndpointDetail && endpointColor == lcdEndpointColor) {
        return;
    }

    lcdEndpointText = endpointText;
    lcdEndpointDetail = endpointDetail;
    lcdEndpointColor = endpointColor;

    int x = endpointCardX + 10;
    int y = topRowY + 26;
    int w = cardWidth - 20;
    int h = cardHeight - 34;

    tft.fillRect(x, y, w, h, UI_CARD);

    tft.fillCircle(x + 8, y + 12, 5, endpointColor);

    tft.setTextSize(2);
    tft.setTextColor(endpointColor, UI_CARD);
    tft.setCursor(x + 20, y + 4);
    tft.print(shortenText(endpointText, 14));

    tft.setTextSize(1);
    tft.setTextColor(TFT_WHITE, UI_CARD);
    tft.setCursor(x + 2, y + 30);
    tft.print(shortenText(endpointDetail, 32));
}

void updateLastFingerprintOnLcd(int fingerprintId)
{
    if (fingerprintId == lcdLastFingerprintId) {
        return;
    }

    lcdLastFingerprintId = fingerprintId;

    int x = endpointCardX + 10;
    int y = secondRowY + 26;
    int w = cardWidth - 20;
    int h = cardHeight - 34;

    tft.fillRect(x, y, w, h, UI_CARD);

    tft.setTextSize(2);
    tft.setTextColor(TFT_CYAN, UI_CARD);
    tft.setCursor(x + 2, y + 8);

    if (fingerprintId > 0) {
        tft.print(fingerprintId);
    } else {
        tft.print("-");
    }
}

void showAlertOnLcd(const String& title, const String& details, uint16_t color)
{
    if (title == lcdAlertTitle && details == lcdAlertDetail && color == lcdAlertColor) {
        return;
    }

    lcdAlertTitle = title;
    lcdAlertDetail = details;
    lcdAlertColor = color;

    tft.fillRoundRect(messagePanelX + 3, messagePanelY + 20, messagePanelW - 6, messagePanelH - 24, 8, UI_CARD);
    tft.fillRect(messagePanelX + 6, messagePanelY + 24, 5, messagePanelH - 32, color);

    String line1;
    String line2;
    splitToTwoLines(details, line1, line2);

    tft.setTextSize(2);
    tft.setTextColor(color, UI_CARD);
    tft.setCursor(messagePanelX + 16, messagePanelY + 28);
    tft.print(shortenText(title, 24));

    tft.setTextSize(1);
    tft.setTextColor(TFT_WHITE, UI_CARD);
    tft.setCursor(messagePanelX + 16, messagePanelY + 54);
    tft.print(shortenText(line1, 64));

    tft.setCursor(messagePanelX + 16, messagePanelY + 68);
    tft.print(shortenText(line2, 64));
}

void splitToTwoLines(const String& text, String& line1, String& line2)
{
    const int maxCharsPerLine = 58;

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

String shortenText(const String& text, int maxLength)
{
    if (maxLength <= 3 || text.length() <= maxLength) {
        return text;
    }

    return text.substring(0, maxLength - 3) + "...";
}

void initClockService()
{
    if (clockServiceInitialized) {
        return;
    }

    configTime(0, 0, NTP_SERVER);
    setenv("TZ", CLOCK_TIMEZONE, 1);
    tzset();
    clockServiceInitialized = true;
}

bool syncClockWithNtp(bool forced)
{
    if (WiFi.status() != WL_CONNECTED) {
        clockSynced = false;
        return false;
    }

    if (!forced && millis() - lastClockSyncAttemptAt < CLOCK_SYNC_RETRY_INTERVAL_MS) {
        return clockSynced;
    }

    lastClockSyncAttemptAt = millis();
    initClockService();

    struct tm timeInfo;

    if (getLocalTime(&timeInfo, 1800)) {
        clockSynced = true;
        return true;
    }

    clockSynced = false;
    return false;
}

String getCurrentClockTimeText()
{
    struct tm timeInfo;

    if (!getLocalTime(&timeInfo, 30)) {
        if (lcdClockText.length() > 0) {
            return lcdClockText;
        }

        return "--:--:--";
    }

    char buffer[9];
    strftime(buffer, sizeof(buffer), "%H:%M:%S", &timeInfo);
    return String(buffer);
}

String getCurrentClockStamp()
{
    return "Jam " + getCurrentClockTimeText();
}

void updateClockOnLcd()
{
    if (clockWidgetW <= 0 || clockWidgetH <= 0) {
        return;
    }

    String clockText = clockSynced ? getCurrentClockTimeText() : "--:--:--";
    uint16_t clockColor = clockSynced ? TFT_WHITE : UI_WARN;

    if (clockText == lcdClockText && clockColor == lcdClockColor) {
        return;
    }

    lcdClockText = clockText;
    lcdClockColor = clockColor;

    uint16_t bgColor = clockSynced ? tft.color565(6, 76, 112) : tft.color565(96, 66, 17);

    tft.fillRoundRect(clockWidgetX, clockWidgetY, clockWidgetW, clockWidgetH, 8, bgColor);
    tft.drawRoundRect(clockWidgetX, clockWidgetY, clockWidgetW, clockWidgetH, 8, TFT_WHITE);

    tft.setTextSize(2);
    tft.setTextColor(clockColor, bgColor);
    tft.setCursor(clockWidgetX + 4, clockWidgetY + 6);
    tft.print(clockText);
}

void loadConfig()
{
    preferences.begin("absensi", true);
    deviceConfig.wifiSsid = preferences.getString("wifi_ssid", DEFAULT_WIFI_SSID);
    deviceConfig.wifiPassword = preferences.getString("wifi_pass", DEFAULT_WIFI_PASSWORD);
    deviceConfig.apiBaseUrl = preferences.getString("api_base", DEFAULT_API_BASE_URL);
    deviceConfig.apiToken = preferences.getString("api_token", DEFAULT_API_TOKEN);
    preferences.end();

    deviceConfig.apiBaseUrl = normalizeBaseUrl(deviceConfig.apiBaseUrl);

    if (deviceConfig.apiToken.length() == 0) {
        deviceConfig.apiToken = DEFAULT_API_TOKEN;
    }
}

void saveConfig(const DeviceConfig& config)
{
    preferences.begin("absensi", false);
    preferences.putString("wifi_ssid", config.wifiSsid);
    preferences.putString("wifi_pass", config.wifiPassword);
    preferences.putString("api_base", config.apiBaseUrl);
    preferences.putString("api_token", config.apiToken);
    preferences.end();

    deviceConfig = config;
}

String normalizeBaseUrl(const String& rawUrl)
{
    String normalized = rawUrl;
    normalized.trim();

    while (normalized.endsWith("/")) {
        normalized.remove(normalized.length() - 1);
    }

    return normalized;
}

String buildApiUrl(const String& path)
{
    String base = normalizeBaseUrl(deviceConfig.apiBaseUrl);
    return base + path;
}

String mapAttendanceStatusLabel(const String& status)
{
    if (status == "arrived") {
        return "Masuk Tepat Waktu";
    }

    if (status == "late") {
        return "Masuk Terlambat";
    }

    if (status == "departed") {
        return "Pulang";
    }

    if (status == "early_leave") {
        return "Pulang Lebih Awal";
    }

    if (status.length() == 0) {
        return "Status Tidak Diketahui";
    }

    String normalized = status;
    normalized.replace("_", " ");

    if (normalized.length() > 0) {
        char first = normalized[0];
        if (first >= 'a' && first <= 'z') {
            normalized.setCharAt(0, static_cast<char>(first - 32));
        }
    }

    return normalized;
}

bool connectWiFi()
{
    if (setupModeActive) {
        return false;
    }

    if (deviceConfig.wifiSsid.length() == 0) {
        showAlertOnLcd("WiFi Gagal", "SSID kosong, masuk mode setup", UI_ERROR);
        return false;
    }

    setModeOnLcd("WIFI CONNECT");
    showAlertOnLcd("WiFi", String("Menghubungkan ke ") + deviceConfig.wifiSsid, UI_ACCENT);

    WiFi.mode(WIFI_STA);
    WiFi.begin(deviceConfig.wifiSsid.c_str(), deviceConfig.wifiPassword.c_str());

    Serial.print("Connecting to WiFi");
    int attempts = 0;

    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
        Serial.print("WiFi connected: ");
        Serial.println(WiFi.localIP());

        bool timeReady = syncClockWithNtp(true);

        updateWifiOnLcd();
        updateClockOnLcd();

        String detail = WiFi.localIP().toString();
        detail += timeReady ? " | Jam sinkron" : " | Jam belum sinkron";

        showAlertOnLcd("WiFi Terhubung", detail, timeReady ? UI_OK : UI_WARN);
        probeEndpoint(true);
        return true;
    }

    Serial.println("WiFi connection failed");
    setEndpointState("OFFLINE", "Server belum bisa dihubungi", UI_WARN);
    updateWifiOnLcd();
    showAlertOnLcd("WiFi Gagal", "Periksa SSID/password, lalu tahan SETUP", UI_ERROR);
    return false;
}

void ensureWiFiConnected()
{
    if (setupModeActive || WiFi.status() == WL_CONNECTED) {
        return;
    }

    if (millis() - lastWiFiRetryAt < WIFI_RETRY_INTERVAL_MS) {
        return;
    }

    lastWiFiRetryAt = millis();
    setModeOnLcd("WIFI RETRY");
    connectWiFi();

    if (WiFi.status() == WL_CONNECTED) {
        setModeOnLcd("IDLE");
    }
}

void setEndpointState(const String& state, const String& detail, uint16_t color)
{
    endpointStateText = state;
    endpointStateDetail = detail;
    endpointStateColor = color;
}

void probeEndpoint(bool forced)
{
    if (setupModeActive) {
        return;
    }

    if (!forced && millis() - lastEndpointProbeAt < ENDPOINT_PROBE_INTERVAL_MS) {
        return;
    }

    lastEndpointProbeAt = millis();

    if (WiFi.status() != WL_CONNECTED) {
        setEndpointState("OFFLINE", "WiFi belum terhubung", UI_WARN);
        return;
    }

    HTTPClient http;
    String url = buildApiUrl("/api/enroll/latest");

    http.begin(url);
    http.setTimeout(3000);
    http.addHeader("Authorization", String("Bearer ") + deviceConfig.apiToken);

    int httpCode = http.GET();
    http.end();

    if (httpCode <= 0) {
        setEndpointState("DOWN", "Tidak ada respon endpoint", UI_ERROR);
        return;
    }

    if (httpCode >= 200 && httpCode < 300) {
        setEndpointState("ONLINE", "Endpoint aktif (" + String(httpCode) + ")", UI_OK);
        return;
    }

    if (httpCode == 401 || httpCode == 403) {
        setEndpointState("AUTH FAIL", "Token endpoint tidak valid", UI_ERROR);
        return;
    }

    setEndpointState("HTTP " + String(httpCode), "Endpoint terjangkau, cek API path", UI_WARN);
}

void handleTouchInput()
{
    if (setupModeActive) {
        return;
    }

    uint16_t touchX = 0;
    uint16_t touchY = 0;
    bool touched = readTouchPoint(touchX, touchY);

    if (!touched) {
        setupButtonTouchStartAt = 0;

        if (setupButtonPressedVisual) {
            drawConfigButton(false);
            setupButtonPressedVisual = false;
        }

        return;
    }

    if (!isConfigButtonArea(touchX, touchY)) {
        setupButtonTouchStartAt = 0;

        if (setupButtonPressedVisual) {
            drawConfigButton(false);
            setupButtonPressedVisual = false;
        }

        return;
    }

    if (!setupButtonPressedVisual) {
        drawConfigButton(true);
        setupButtonPressedVisual = true;
    }

    if (setupButtonTouchStartAt == 0) {
        setupButtonTouchStartAt = millis();
        return;
    }

    if (millis() - setupButtonTouchStartAt < TOUCH_HOLD_TO_SETUP_MS) {
        return;
    }

    if (millis() - lastSetupTriggerAt < 3000) {
        return;
    }

    lastSetupTriggerAt = millis();
    setupButtonTouchStartAt = 0;
    setupButtonPressedVisual = false;
    drawConfigButton(false);
    startSetupMode();
}

void startSetupMode()
{
    if (setupModeActive) {
        return;
    }

    setupModeActive = true;
    setEndpointState("CONFIG MODE", "Edit langsung di layar", UI_ACCENT);

    setupEditWifiSsid = deviceConfig.wifiSsid;
    setupEditWifiPassword = deviceConfig.wifiPassword;
    setupEditApiBaseUrl = deviceConfig.apiBaseUrl;
    setupEditApiToken = deviceConfig.apiToken;

    setupFieldValues[0] = &setupEditWifiSsid;
    setupFieldValues[1] = &setupEditWifiPassword;
    setupFieldValues[2] = &setupEditApiBaseUrl;
    setupFieldValues[3] = &setupEditApiToken;

    setupSelectedFieldIndex = 0;
    setupKeyboardUppercase = false;
    setupEditorTouchLatched = false;

    configureSetupButtons();
    configureSetupKeyboardButtons();
    setSetupEditorNotice("Mode setup lokal aktif. Tap field lalu ketik di keyboard.", UI_ACCENT);
    drawSetupEditor();

    Serial.println("Setup mode active: local touchscreen editor");
}

void stopSetupMode()
{
    int previousFingerprintId = lcdLastFingerprintId;

    setupModeActive = false;
    setupEditorTouchLatched = false;
    setupKeyboardUppercase = false;
    setupButtonTouchStartAt = 0;
    setupButtonPressedVisual = false;

    lcdModeText = "";
    lcdWifiText = "";
    lcdWifiDetail = "";
    lcdEndpointText = "";
    lcdEndpointDetail = "";
    lcdAlertTitle = "";
    lcdAlertDetail = "";
    lcdLastFingerprintId = -9999;

    drawDashboardLayout();
    updateWifiOnLcd();
    updateEndpointOnLcd();
    updateLastFingerprintOnLcd(previousFingerprintId > 0 ? previousFingerprintId : -1);
}

void configureSetupButtons()
{
    const int buttonWidth = 56;
    const int buttonHeight = 28;
    const int gap = 2;
    const int rowY = 268;
    const int startX = 9;

    const char* labels[SETUP_BUTTON_COUNT] = {
        "SHIFT", "SPACE", "BKSP", "CLEAR", "_", "@", "SAVE", "EXIT"
    };

    for (int i = 0; i < SETUP_BUTTON_COUNT; i++) {
        setupButtons[i].x = startX + (i * (buttonWidth + gap));
        setupButtons[i].y = rowY;
        setupButtons[i].w = buttonWidth;
        setupButtons[i].h = buttonHeight;
        setupButtons[i].label = labels[i];
    }
}

void configureSetupKeyboardButtons()
{
    const int keyWidth = 44;
    const int keyHeight = 24;
    const int gap = 2;
    const int startX = 10;
    const int startY = 164;

    for (int row = 0; row < SETUP_KEY_ROWS; row++) {
        for (int col = 0; col < SETUP_KEY_COLS; col++) {
            int keyIndex = row * SETUP_KEY_COLS + col;
            setupKeyButtons[keyIndex].x = startX + (col * (keyWidth + gap));
            setupKeyButtons[keyIndex].y = startY + (row * (keyHeight + gap));
            setupKeyButtons[keyIndex].w = keyWidth;
            setupKeyButtons[keyIndex].h = keyHeight;
            setupKeyButtons[keyIndex].label = nullptr;
        }
    }
}

void drawSetupEditor()
{
    tft.fillScreen(UI_BG);

    tft.fillRect(0, 0, lcdWidth, 36, UI_HEADER);
    tft.setTextSize(2);
    tft.setTextColor(TFT_WHITE, UI_HEADER);
    tft.setCursor(10, 10);
    tft.print("Setup Lokal Device");

    tft.setTextSize(1);
    tft.setTextColor(TFT_WHITE, UI_HEADER);
    tft.setCursor(lcdWidth - 140, 12);
    tft.print(String("Touch CS: ") + TOUCHSCREEN_CS_PIN);

    for (int fieldIndex = 0; fieldIndex < SETUP_FIELD_COUNT; fieldIndex++) {
        drawSetupFieldRow(fieldIndex);
    }

    tft.setTextSize(1);
    tft.setTextColor(UI_ACCENT, UI_CARD);
    tft.setCursor(10, 154);
    tft.print("Keyboard touch: pilih field lalu ketik karakter");

    drawSetupKeyboard();

    for (int i = 0; i < SETUP_BUTTON_COUNT; i++) {
        const SetupButton& button = setupButtons[i];

        uint16_t buttonColor = tft.color565(7, 90, 130);
        if (i == 0 && setupKeyboardUppercase) {
            buttonColor = tft.color565(30, 122, 170);
        }
        if (i == 6) {
            buttonColor = tft.color565(20, 125, 68);
        }
        if (i == 7) {
            buttonColor = tft.color565(130, 84, 26);
        }

        tft.fillRoundRect(button.x, button.y, button.w, button.h, 8, buttonColor);
        tft.drawRoundRect(button.x, button.y, button.w, button.h, 8, TFT_WHITE);

        tft.setTextSize(1);
        tft.setTextColor(TFT_WHITE, buttonColor);
        int labelLength = String(button.label).length();
        int textX = button.x + ((button.w - (labelLength * 6)) / 2);
        if (textX < button.x + 2) {
            textX = button.x + 2;
        }
        tft.setCursor(textX, button.y + 10);
        tft.print(button.label);
    }

    drawSetupEditorNotice(setupEditorNotice, setupEditorNoticeColor);
}

void drawSetupKeyboard()
{
    for (int keyIndex = 0; keyIndex < SETUP_KEY_ROWS * SETUP_KEY_COLS; keyIndex++) {
        const SetupButton& keyButton = setupKeyButtons[keyIndex];
        char keyChar = resolveSetupKeyChar(keyIndex);

        if (keyChar == '\0') {
            continue;
        }

        int row = keyIndex / SETUP_KEY_COLS;
        uint16_t keyColor = row == 0 ? tft.color565(10, 58, 88) : tft.color565(14, 74, 107);

        tft.fillRoundRect(keyButton.x, keyButton.y, keyButton.w, keyButton.h, 5, keyColor);
        tft.drawRoundRect(keyButton.x, keyButton.y, keyButton.w, keyButton.h, 5, UI_CARD_BORDER);

        char keyLabel[2] = { keyChar, '\0' };
        tft.setTextSize(1);
        tft.setTextColor(TFT_WHITE, keyColor);
        tft.setCursor(keyButton.x + (keyButton.w / 2) - 3, keyButton.y + 8);
        tft.print(keyLabel);
    }
}

void drawSetupFieldRow(int fieldIndex)
{
    if (fieldIndex < 0 || fieldIndex >= SETUP_FIELD_COUNT || setupFieldValues[fieldIndex] == nullptr) {
        return;
    }

    const int rowX = 10;
    const int rowY = 44 + (fieldIndex * 30);
    const int rowW = lcdWidth - 20;
    const int rowH = 26;

    bool selected = fieldIndex == setupSelectedFieldIndex;
    uint16_t rowColor = selected ? tft.color565(24, 79, 106) : UI_CARD;

    tft.fillRoundRect(rowX, rowY, rowW, rowH, 7, rowColor);
    tft.drawRoundRect(rowX, rowY, rowW, rowH, 7, selected ? UI_ACCENT : UI_CARD_BORDER);

    tft.setTextSize(1);
    tft.setTextColor(UI_ACCENT, rowColor);
    tft.setCursor(rowX + 8, rowY + 4);
    tft.print(setupFieldLabels[fieldIndex]);

    String preview = setupFieldPreviewValue(fieldIndex, *setupFieldValues[fieldIndex]);
    tft.setTextColor(TFT_WHITE, rowColor);
    tft.setCursor(rowX + 160, rowY + 4);
    tft.print(shortenText(preview, 49));
}

void drawSetupEditorNotice(const String& notice, uint16_t color)
{
    const int boxX = 10;
    const int boxY = 298;
    const int boxW = lcdWidth - 20;
    const int boxH = 20;

    tft.fillRoundRect(boxX, boxY, boxW, boxH, 8, UI_CARD);
    tft.drawRoundRect(boxX, boxY, boxW, boxH, 8, color);

    tft.setTextSize(1);
    tft.setTextColor(color, UI_CARD);
    tft.setCursor(boxX + 8, boxY + 6);
    tft.print(shortenText(notice, 74));
}

int hitTestSetupField(uint16_t x, uint16_t y)
{
    for (int fieldIndex = 0; fieldIndex < SETUP_FIELD_COUNT; fieldIndex++) {
        int rowX = 10;
        int rowY = 44 + (fieldIndex * 30);
        int rowW = lcdWidth - 20;
        int rowH = 26;

        if (x >= rowX && x <= rowX + rowW && y >= rowY && y <= rowY + rowH) {
            return fieldIndex;
        }
    }

    return -1;
}

int hitTestSetupButton(uint16_t x, uint16_t y)
{
    for (int i = 0; i < SETUP_BUTTON_COUNT; i++) {
        const SetupButton& button = setupButtons[i];

        if (x >= button.x && x <= button.x + button.w && y >= button.y && y <= button.y + button.h) {
            return i;
        }
    }

    return -1;
}

int hitTestSetupKey(uint16_t x, uint16_t y)
{
    for (int keyIndex = 0; keyIndex < SETUP_KEY_ROWS * SETUP_KEY_COLS; keyIndex++) {
        const SetupButton& keyButton = setupKeyButtons[keyIndex];

        if (x >= keyButton.x && x <= keyButton.x + keyButton.w && y >= keyButton.y && y <= keyButton.y + keyButton.h) {
            return keyIndex;
        }
    }

    return -1;
}

char resolveSetupKeyChar(int keyIndex)
{
    if (keyIndex < 0 || keyIndex >= SETUP_KEY_ROWS * SETUP_KEY_COLS) {
        return '\0';
    }

    int row = keyIndex / SETUP_KEY_COLS;
    int col = keyIndex % SETUP_KEY_COLS;
    char keyChar = SETUP_KEYBOARD_LAYOUT[row][col];

    if (setupKeyboardUppercase && keyChar >= 'a' && keyChar <= 'z') {
        keyChar = static_cast<char>(keyChar - 32);
    }

    return keyChar;
}

String repeatChar(char fill, int count)
{
    String repeated = "";

    for (int i = 0; i < count; i++) {
        repeated += fill;
    }

    return repeated;
}

String setupFieldPreviewValue(int fieldIndex, const String& value)
{
    if (value.length() == 0) {
        return "(kosong)";
    }

    if (fieldIndex == 1) {
        return repeatChar('*', value.length());
    }

    return value;
}

void setSetupEditorNotice(const String& notice, uint16_t color)
{
    setupEditorNotice = notice;
    setupEditorNoticeColor = color;
}

bool appendCharToActiveSetupField(char character)
{
    if (setupSelectedFieldIndex < 0 || setupSelectedFieldIndex >= SETUP_FIELD_COUNT || setupFieldValues[setupSelectedFieldIndex] == nullptr) {
        return false;
    }

    String& activeFieldValue = *setupFieldValues[setupSelectedFieldIndex];

    if (activeFieldValue.length() >= static_cast<unsigned int>(setupFieldMaxLength[setupSelectedFieldIndex])) {
        setSetupEditorNotice("Panjang field sudah maksimal.", UI_WARN);
        return false;
    }

    activeFieldValue += character;

    if (character == ' ') {
        setSetupEditorNotice("Input: SPACE", UI_OK);
    } else {
        setSetupEditorNotice(String("Input: ") + character, UI_OK);
    }

    return true;
}

void applySetupEditorAction(int actionIndex)
{
    if (setupSelectedFieldIndex < 0 || setupSelectedFieldIndex >= SETUP_FIELD_COUNT || setupFieldValues[setupSelectedFieldIndex] == nullptr) {
        return;
    }

    String& activeFieldValue = *setupFieldValues[setupSelectedFieldIndex];

    switch (actionIndex) {
        case 0: {
            setupKeyboardUppercase = !setupKeyboardUppercase;
            setSetupEditorNotice(setupKeyboardUppercase ? "SHIFT aktif (huruf besar)." : "SHIFT nonaktif (huruf kecil).", UI_ACCENT);
            break;
        }

        case 1: {
            appendCharToActiveSetupField(' ');
            break;
        }

        case 2: {
            if (activeFieldValue.length() == 0) {
                setSetupEditorNotice("Field sudah kosong.", UI_WARN);
                break;
            }

            activeFieldValue.remove(activeFieldValue.length() - 1);
            setSetupEditorNotice("Karakter terakhir dihapus.", UI_WARN);
            break;
        }

        case 3: {
            if (activeFieldValue.length() == 0) {
                setSetupEditorNotice("Field sudah kosong.", UI_WARN);
                break;
            }

            activeFieldValue = "";
            setSetupEditorNotice("Isi field dibersihkan.", UI_WARN);
            break;
        }

        case 4: {
            appendCharToActiveSetupField('_');
            break;
        }

        case 5: {
            appendCharToActiveSetupField('@');
            break;
        }

        case 6: {
            if (setupEditWifiSsid.length() == 0 || setupEditApiBaseUrl.length() == 0 || setupEditApiToken.length() == 0) {
                setSetupEditorNotice("SSID, endpoint, dan token wajib terisi.", UI_ERROR);
                break;
            }

            if (!setupEditApiBaseUrl.startsWith("http://") && !setupEditApiBaseUrl.startsWith("https://")) {
                setSetupEditorNotice("Endpoint harus diawali http:// atau https://", UI_WARN);
                break;
            }

            DeviceConfig newConfig;
            newConfig.wifiSsid = setupEditWifiSsid;
            newConfig.wifiPassword = setupEditWifiPassword;
            newConfig.apiBaseUrl = normalizeBaseUrl(setupEditApiBaseUrl);
            newConfig.apiToken = setupEditApiToken;

            saveConfig(newConfig);
            stopSetupMode();

            showAlertOnLcd("Setup Tersimpan", "Konfigurasi baru aktif. Mencoba reconnect...", UI_OK);
            setModeOnLcd("WIFI CONNECT");
            connectWiFi();
            probeEndpoint(true);
            setModeOnLcd(WiFi.status() == WL_CONNECTED ? "IDLE" : "WIFI OFFLINE");
            return;
        }

        case 7: {
            stopSetupMode();
            showAlertOnLcd("Setup Ditutup", "Perubahan dibatalkan", UI_WARN);
            probeEndpoint(true);
            setModeOnLcd(WiFi.status() == WL_CONNECTED ? "IDLE" : "WIFI OFFLINE");
            return;
        }

        default: {
            return;
        }
    }

    drawSetupEditor();
}

void handleSetupEditorTouch()
{
    if (!setupModeActive) {
        return;
    }

    uint16_t touchX = 0;
    uint16_t touchY = 0;
    bool touched = readTouchPoint(touchX, touchY);

    if (!touched) {
        setupEditorTouchLatched = false;
        return;
    }

    if (setupEditorTouchLatched) {
        return;
    }

    setupEditorTouchLatched = true;

    int fieldHit = hitTestSetupField(touchX, touchY);
    if (fieldHit >= 0) {
        setupSelectedFieldIndex = fieldHit;
        setSetupEditorNotice(String("Field aktif: ") + setupFieldLabels[fieldHit], UI_ACCENT);
        drawSetupEditor();
        return;
    }

    int keyHit = hitTestSetupKey(touchX, touchY);
    if (keyHit >= 0) {
        char keyChar = resolveSetupKeyChar(keyHit);
        if (keyChar != '\0') {
            appendCharToActiveSetupField(keyChar);
            drawSetupEditor();
        }
        return;
    }

    int buttonHit = hitTestSetupButton(touchX, touchY);
    if (buttonHit >= 0) {
        applySetupEditorAction(buttonHit);
    }
}

bool readTouchPoint(uint16_t& touchX, uint16_t& touchY)
{
    // Keep the default TFT_eSPI path first; some panels are already calibrated.
    if (tft.getTouch(&touchX, &touchY, 450)) {
        return true;
    }

    // Fallback based on the previously working project: raw read + pressure threshold.
    uint16_t rawX = 0;
    uint16_t rawY = 0;

    if (!tft.getTouchRaw(&rawX, &rawY)) {
        return false;
    }

    if (tft.getTouchRawZ() <= TOUCH_RAW_Z_MIN) {
        return false;
    }

    long mappedX = map(rawY, TOUCH_RAW_MIN, TOUCH_RAW_MAX, 0, lcdWidth - 1);
    long mappedY = map(rawX, TOUCH_RAW_MIN, TOUCH_RAW_MAX, 0, lcdHeight - 1);

    mappedX = constrain(mappedX, 0, lcdWidth - 1);
    mappedY = constrain(mappedY, 0, lcdHeight - 1);

    touchX = static_cast<uint16_t>(mappedX);
    touchY = static_cast<uint16_t>(mappedY);

    return true;
}

bool initFingerprintSensor()
{
    FingerSerial.begin(FP_BAUDRATE, SERIAL_8N1, FP_RX_PIN, FP_TX_PIN);
    finger.begin(FP_BAUDRATE);

    if (finger.verifyPassword()) {
        Serial.println("Fingerprint sensor detected");
        showAlertOnLcd("Fingerprint OK", "Sensor terdeteksi dan siap", UI_OK);
        return true;
    }

    Serial.println("Fingerprint sensor not detected");
    showAlertOnLcd("Fingerprint Error", "Sensor tidak terdeteksi", UI_ERROR);
    return false;
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
        showAlertOnLcd("Absensi Gagal", "WiFi tidak terhubung", UI_ERROR);
        return;
    }

    setModeOnLcd("KIRIM ABSENSI");
    showAlertOnLcd("Absensi", String("Mengirim ID ") + userId + " ke server", UI_ACCENT);

    HTTPClient http;
    String url = buildApiUrl("/api/attendance");

    http.begin(url);
    http.setTimeout(5000);
    http.addHeader("Authorization", String("Bearer ") + deviceConfig.apiToken);
    http.addHeader("Content-Type", "application/json");

    String body = String("{\"user_id\":") + userId + "}";
    int httpCode = http.POST(body);
    String response = http.getString();

    Serial.print("POST /api/attendance code: ");
    Serial.println(httpCode);
    Serial.println(response);

    http.end();

    if (httpCode <= 0) {
        showAlertOnLcd("Absensi Gagal", "Tidak bisa menghubungi server", UI_ERROR);
        setEndpointState("DOWN", "Koneksi endpoint terputus", UI_ERROR);
        setModeOnLcd("IDLE");
        return;
    }

    if (httpCode == 401 || httpCode == 403) {
        setEndpointState("AUTH FAIL", "Token endpoint tidak valid", UI_ERROR);
    } else {
        setEndpointState("ONLINE", "Attendance endpoint aktif", UI_OK);
    }

    String topStatus = extractJsonString(response, "status");
    String message = extractJsonString(response, "message");
    int userObjectIndex = response.indexOf("\"user\":");
    String userName = userObjectIndex >= 0 ? extractJsonString(response, "name", userObjectIndex) : "";
    int dataObjectIndex = response.indexOf("\"data\":");
    String attendanceStatus = dataObjectIndex >= 0 ? extractJsonString(response, "status", dataObjectIndex) : "";
    String attendanceStatusLabel = mapAttendanceStatusLabel(attendanceStatus);

    if (topStatus == "success") {
        String detail = message;
        String attendanceTime = getCurrentClockStamp();

        if (userName.length() > 0) {
            detail = userName + " | " + detail;
        }

        if (attendanceStatus == "departed" || attendanceStatus == "early_leave") {
            detail += " | Pulang " + attendanceTime;
        } else {
            detail += " | Absen " + attendanceTime;
        }

        if (attendanceStatus.length() > 0) {
            detail += " (" + attendanceStatusLabel + ")";
        }

        showAlertOnLcd("Absensi Berhasil", detail, UI_OK);
    } else {
        if (message.length() == 0) {
            message = "Format response tidak valid";
        }

        showAlertOnLcd("Absensi Gagal", message, UI_ERROR);
    }

    setModeOnLcd("IDLE");
}

void pollEnrollRequest()
{
    if (WiFi.status() != WL_CONNECTED || setupModeActive) {
        return;
    }

    HTTPClient http;
    String url = buildApiUrl("/api/enroll/latest");

    http.begin(url);
    http.setTimeout(4000);
    http.addHeader("Authorization", String("Bearer ") + deviceConfig.apiToken);

    int httpCode = http.GET();
    String response = http.getString();

    http.end();

    if (httpCode == 401 || httpCode == 403) {
        setEndpointState("AUTH FAIL", "Token enroll endpoint invalid", UI_ERROR);
        return;
    }

    if (httpCode <= 0) {
        setEndpointState("DOWN", "Gagal cek enroll latest", UI_ERROR);
        return;
    }

    if (httpCode != 200) {
        setEndpointState("HTTP " + String(httpCode), "Endpoint enroll merespon", UI_WARN);
        return;
    }

    setEndpointState("ONLINE", "Enroll endpoint aktif", UI_OK);

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
    showAlertOnLcd("Enroll", String("ID ") + id + " tempel jari pertama", UI_ACCENT);

    while (p != FINGERPRINT_OK) {
        p = finger.getImage();

        if (p == FINGERPRINT_NOFINGER) {
            delay(50);
        }
    }

    p = finger.image2Tz(1);

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Gagal membaca jari pertama", UI_ERROR);
        return false;
    }

    Serial.println("Remove finger");
    showAlertOnLcd("Enroll", "Lepas jari dari sensor", UI_ACCENT);
    delay(2000);

    p = 0;

    while (p != FINGERPRINT_NOFINGER) {
        p = finger.getImage();
        delay(50);
    }

    Serial.println("Place same finger again");
    showAlertOnLcd("Enroll", "Tempel jari yang sama lagi", UI_ACCENT);
    p = -1;

    while (p != FINGERPRINT_OK) {
        p = finger.getImage();

        if (p == FINGERPRINT_NOFINGER) {
            delay(50);
        }
    }

    p = finger.image2Tz(2);

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Gagal membaca jari kedua", UI_ERROR);
        return false;
    }

    p = finger.createModel();

    if (p != FINGERPRINT_OK) {
        showAlertOnLcd("Enroll Gagal", "Sidik jari tidak cocok", UI_ERROR);
        return false;
    }

    p = finger.storeModel(id);

    if (p == FINGERPRINT_OK) {
        Serial.println("Enroll done");
        showAlertOnLcd("Enroll Berhasil", String("ID ") + id + " tersimpan", UI_OK);
        return true;
    }

    showAlertOnLcd("Enroll Gagal", "Tidak dapat menyimpan ke sensor", UI_ERROR);
    return false;
}

void postEnrollDone(int fingerprintId, const char* status)
{
    if (WiFi.status() != WL_CONNECTED) {
        return;
    }

    HTTPClient http;
    String url = buildApiUrl("/api/enroll/done");

    http.begin(url);
    http.setTimeout(5000);
    http.addHeader("Authorization", String("Bearer ") + deviceConfig.apiToken);
    http.addHeader("Content-Type", "application/json");

    String body = String("{\"fingerprint_id\":") + fingerprintId + ",\"status\":\"" + status + "\"}";
    int httpCode = http.POST(body);
    String response = http.getString();

    Serial.print("POST /api/enroll/done code: ");
    Serial.println(httpCode);
    Serial.println(response);

    http.end();

    if (httpCode <= 0) {
        showAlertOnLcd("Sync Enroll Gagal", "Status enroll tidak terkirim", UI_ERROR);
        setEndpointState("DOWN", "Gagal kirim enroll result", UI_ERROR);
        return;
    }

    if (httpCode == 401 || httpCode == 403) {
        setEndpointState("AUTH FAIL", "Token enroll endpoint invalid", UI_ERROR);
    } else {
        setEndpointState("ONLINE", "Sync enroll endpoint aktif", UI_OK);
    }

    String topStatus = extractJsonString(response, "status");
    String message = extractJsonString(response, "message");

    if (topStatus == "success") {
        if (message.length() == 0) {
            message = String("ID ") + fingerprintId + " status " + status + " terkirim";
        }

        showAlertOnLcd("Sync Enroll", message, UI_OK);
    } else {
        if (message.length() == 0) {
            message = "Server menolak update enroll";
        }

        showAlertOnLcd("Sync Enroll Gagal", message, UI_ERROR);
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
