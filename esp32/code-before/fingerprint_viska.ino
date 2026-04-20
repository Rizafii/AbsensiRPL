#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <esp_sntp.h>
#include <time.h>
#include <SPI.h>
#include <lvgl.h>
#include <TFT_eSPI.h>
#include "ui.h"
#include <EEPROM.h>
#include <ArduinoJson.h>
#include <Adafruit_Fingerprint.h>

//Global Variable
#define EEPROM_SIZE 70
WebServer server(80);
bool firstboot = true;
bool startap = false;
bool startwifi = false;
bool apstatus = false;
bool apstartconnect = false;
bool apconnecting = false;
bool wificonnecting = false;
bool startclock = false;
bool clockstatus = false;
bool fingerstate = true;
bool cooldownconnecting = true;
bool connectionpopup = false;
int wifitimeouttimer = 0; //in ms
int wificooldowntimer = 0; //in ms
int wifitimeout = 10000; //in ms
int wificooldown = 10000; //in ms
int screencooldown = 10; //in second
int screencooldowntimer = 0; //in second
unsigned long previousMillis = 0;
unsigned long previousMillis2 = 0;
unsigned long previousMillis3 = 0;
char stassid[32] = {0};
char stapsk[32] = {0};
char id1[3] = "";
char id2[3] = "";
char btn1[3] = "";
char btn2[3] = "";

const char* namaHari[] = {
  "Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"
};
const char* namaBulan[] = {
  "Januari", "Februari", "Maret", "April", "Mei", "Juni",
  "Juli", "Agustus", "September", "Oktober", "November", "Desember"
};

// AP credential
#define APSSID "ViskaAbsen2"
#define APPSK "viska2026"

//Clock Setting
const char *ntpServer = "pool.ntp.org";
const char *time_format = "%H:%M:%S";   // eg. 16:45:23
// Timezone strings from https://github.com/nayarsystems/posix_tz_db/blob/master/zones.csv
char *timezone = "WIB-7";      char *timezone_text = "Asia/Jakarta";

//URL Path
String serverName = "http://192.168.12.20:8083/api/users";
const char* apiCheckin = "http://192.168.12.20:8083/api/attendance";
const char* authToken = "jgk0advefk90gj4ngin4290";

// Defines the T_CS Touchscreen PIN.
#define T_CS_PIN 13  //--> T_CS

// Defines the screen resolution.
#define SCREEN_WIDTH 480
#define SCREEN_HEIGHT 320

// LVGL draw into this buffer, 1/10 screen size usually works well.
#define DRAW_BUF_SIZE (SCREEN_WIDTH * SCREEN_HEIGHT / 10 * (LV_COLOR_DEPTH / 8))

HardwareSerial mySerial(2);  // UART2 untuk ESP32
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

uint8_t *draw_buf;

uint32_t lastTick = 0;

int finger_id;

TFT_eSPI tft = TFT_eSPI();

// CUSTOM FUNCTION

//Fingerprint
void log_print(lv_log_level_t level, const char *buf) {
  LV_UNUSED(level);
  Serial.println(buf);
  Serial.flush();
}

void touchscreen_read(lv_indev_t *indev, lv_indev_data_t *data) {
  uint16_t raw_x, raw_y;

  if (tft.getTouchRaw(&raw_x, &raw_y) && tft.getTouchRawZ() > 200) {
    // Sesuaikan dengan hasil kalibrasi kamu
    data->point.x = map(raw_x, 200, 3800, 0, 320);
    data->point.y = map(raw_y, 200, 3800, 0, 480);  // dibalik sumbu Y
    data->state = LV_INDEV_STATE_PRESSED;
    screencooldowntimer = 0;
    if (clockstatus == true) {
      clockstatus = false;
      lv_scr_load(objects.main);
    }
  } else {
    data->state = LV_INDEV_STATE_RELEASED;
  }
}

// EEPROM Save Credential Wifi function
void saveCredential(const char* ssid, const char* pass) {
  EEPROM.writeString(0, ssid);
  EEPROM.writeString(32, pass);
  EEPROM.commit();
}

// EEPROM Load Credential Wifi function
void loadCredential(char* ssid, char* pass) {
  EEPROM.readString(0).toCharArray(ssid, 32);
  EEPROM.readString(32).toCharArray(pass, 32);
}

//time handler
void setTimezone(String timezone)
{
  // Serial.printf("Setting Timezone to %s\n",timezone.c_str());
  setenv("TZ",timezone.c_str(),1);  //  Now adjust the TZ.  Clock settings are adjusted to show the new local time
  tzset();
}

// callback function to show when NTP was synchronized
void cbSyncTime(struct timeval *tv)
{
  printLocalTime();
}

void update_clock_labels()
{
  struct tm timeinfo;
  if(!getLocalTime(&timeinfo))
  {
    Serial.println("Failed to obtain time");
    return;
  }
  int hari = timeinfo.tm_wday;
  int tanggal = timeinfo.tm_mday;
  int bulan = timeinfo.tm_mon;
  int tahun = timeinfo.tm_year + 1900;
  char current_date[64];
  // strftime( current_date, 64, "%A, %B %d %Y", &timeinfo );
  snprintf(current_date, sizeof(current_date), "%s, %d %s %d", namaHari[hari], tanggal, namaBulan[bulan], tahun);
  lv_label_set_text(objects.text_date, current_date);
  char current_time[64];
  strftime( current_time, 64, time_format, &timeinfo );
  lv_label_set_text(objects.text_clock, current_time);
}

void printLocalTime()
{
  struct tm timeinfo;
  if(!getLocalTime(&timeinfo))
  {
    Serial.println("Failed to obtain time");
  }
  else
  {
    Serial.println(&timeinfo, "%A, %B %d %Y %H:%M:%S zone %Z %z");
  }
}


// Event Function Handler


// global

static void showPopupWifiNotConnected() {
  lv_obj_clear_flag(objects.no_wifi_notif, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.no_wifi_notif_1, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.no_wifi_notif_2, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.no_wifi_notif_3, LV_OBJ_FLAG_HIDDEN);
} 

static void hidePopupWifiNotConnected() {
  lv_obj_add_flag(objects.no_wifi_notif, LV_OBJ_FLAG_HIDDEN);
  lv_obj_add_flag(objects.no_wifi_notif_1, LV_OBJ_FLAG_HIDDEN);
  lv_obj_add_flag(objects.no_wifi_notif_2, LV_OBJ_FLAG_HIDDEN);
  lv_obj_add_flag(objects.no_wifi_notif_3, LV_OBJ_FLAG_HIDDEN);
} 


// Main Page
static void button_menu_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke menu");
    lv_scr_load(objects.login_page);
  }
}

static void button_backtomain_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke main");
    lv_scr_load(objects.main); 
  }
}

void showPopupProcessing() {
  fingerstate = false;
  lv_obj_clear_flag(objects.popup_processing, LV_OBJ_FLAG_HIDDEN);    
}

void hidePopupSuccess(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_attendance, LV_OBJ_FLAG_HIDDEN);  
  lv_timer_del(timer);                                            
  fingerstate = true;
}

void showPopupSuccess(String status) {
  lv_label_set_text(objects.text_welcome, status.c_str());
  lv_obj_add_flag(objects.popup_processing, LV_OBJ_FLAG_HIDDEN);    
  lv_obj_clear_flag(objects.popup_attendance, LV_OBJ_FLAG_HIDDEN);    
  lv_timer_t *timer = lv_timer_create(hidePopupSuccess, 2000, NULL);  
}

void hidePopupError(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_attendance, LV_OBJ_FLAG_HIDDEN);  
  lv_obj_add_flag(objects.icon_gagal, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.icon_berhasil, LV_OBJ_FLAG_HIDDEN);
  // lv_obj_clear_flag(objects.text_nama, LV_OBJ_FLAG_HIDDEN);
  fingerstate = true;
  lv_timer_del(timer);  // Hapus timer setelah selesai
}

void showPopupError(const char* message) {
  lv_obj_clear_flag(objects.icon_gagal, LV_OBJ_FLAG_HIDDEN);
  lv_obj_add_flag(objects.icon_berhasil, LV_OBJ_FLAG_HIDDEN);
  lv_label_set_text(objects.text_nama, message);
  lv_label_set_text(objects.text_welcome, "Error");
  lv_obj_add_flag(objects.popup_processing, LV_OBJ_FLAG_HIDDEN);    
  lv_obj_clear_flag(objects.popup_attendance, LV_OBJ_FLAG_HIDDEN);          // Tampilkan panel
  fingerstate = false;
  lv_timer_t *timer = lv_timer_create(hidePopupError, 2000, NULL);  // Buat timer untuk menyembunyikan panel
}

void httpTask(void *param) {
  // Data yang diterima dari parameter task
  uint8_t id = *(uint8_t *)param;

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    http.begin(apiCheckin);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", authToken);

    StaticJsonDocument<1024> doc;
    doc["user_id"] = id;

    String requestBody;
    serializeJson(doc, requestBody);

    // Kirim HTTP POST request
    int httpResponseCode = http.POST(requestBody);

    if (httpResponseCode > 0) {
      // Serial.print("HTTP Response code: ");
      // Serial.println(httpResponseCode);

      String payload = http.getString();
      // Serial.println("Received Payload:");
      // Serial.println(payload);

      // Parse JSON payload
      StaticJsonDocument<1024> responseDoc;
      DeserializationError error = deserializeJson(responseDoc, payload);
      if (!error) {
        String status = responseDoc["status"].as<String>();
        if (status == "success") {
          String name = responseDoc["user"]["name"].as<String>();
          lv_label_set_text(objects.text_nama, name.c_str());
          String type = responseDoc["data"]["status"].as<String>();
          if (type == "departed") {
            showPopupSuccess("Selamat jalan");
          } else {
            showPopupSuccess("Selamat Datang");
          }
        } else {
          const char* errorMsg = responseDoc["message"].as<const char*>();
          showPopupError(errorMsg);
        }
      } else {
        // Serial.print("JSON Deserialization failed: ");
        // Serial.println(error.c_str());
        showPopupError("Invalid server response");
      }
    } else {
      // Serial.print("Error code: ");
      // Serial.println(httpResponseCode);
      showPopupError("Problem with server");
    }

    http.end();  // Akhiri koneksi HTTP
  } else {
    showPopupError("No WiFi connection");
  }

  // Hapus task setelah selesai
  vTaskDelete(NULL);
}


static void checkin_event_handler(uint8_t id) {
  // Salin ID ke memori dinamis agar tetap tersedia untuk task
  uint8_t *idParam = (uint8_t *)malloc(sizeof(uint8_t));
  if (idParam == NULL) {
    showPopupError("Memory allocation failed");
    return;
  }
  
  *idParam = id;

  // Buat task untuk menjalankan HTTP POST
  xTaskCreate(
    httpTask,     // Fungsi task
    "HTTP Task",  // Nama task
    4096,         // Ukuran stack task
    idParam,      // Parameter task (user ID)
    1,            // Prioritas task
    NULL          // Handle task (tidak diperlukan)
  );

  // Tetap perbarui timer LVGL di fungsi utama
  lv_tick_inc(millis() - lastTick);
  lastTick = millis();
  lv_timer_handler();
}

uint8_t getFingerprintID() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) {
    return p;
  }

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    Serial.println("Image converted");
  }

  p = finger.fingerSearch();
  if (p == FINGERPRINT_OK) {
    // Serial.print("Found ID #");
    // Serial.print(finger.fingerID);
    // Serial.print(" with confidence ");
    // Serial.println(finger.confidence);
    if (WiFi.status() == WL_CONNECTED) {
      checkin_event_handler(finger.fingerID);
      screencooldowntimer = 0;
      if (clockstatus == true) {
        clockstatus = false;
        lv_scr_load(objects.main);
      }
      showPopupProcessing();
    } else {
      showPopupError("No Internet Connection!");
    }
  } else {
    // String status = "Not found ID";
    // Serial.println("No match found");
    screencooldowntimer = 0;
    if (clockstatus == true) {
      clockstatus = false;
      lv_scr_load(objects.main);
    }
    showPopupError("Not found ID!");
  }
  return p;
}

uint8_t getFingerprintLogin() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) {
    return p;
  }

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    Serial.println("Image converted");
  }

  p = finger.fingerSearch();
  if (p == FINGERPRINT_OK) {
    if (atoi(id1) == finger.fingerID) {
      lv_scr_load(objects.menu_page);
    } else if (atoi(id2) == finger.fingerID) {
      lv_scr_load(objects.menu_page);
    } else {
      fingerstate = false;
      lv_obj_clear_flag(objects.popup_wrong, LV_OBJ_FLAG_HIDDEN);
      lv_timer_t *timer = lv_timer_create(hidePopupWrong, 2000, NULL);
    }
  } else {
    fingerstate = false;
    lv_obj_clear_flag(objects.popup_wrong, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupWrong, 2000, NULL);
  }
  return p;
}



// Login and Menu Page
static void button_backtomenu_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke menu");
    lv_scr_load(objects.menu_page); // go to menu page
  }
}

static void button_fingerprint_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    if (WiFi.status() != WL_CONNECTED) {
      lv_obj_clear_flag(objects.popup_no_wifi_big, LV_OBJ_FLAG_HIDDEN);
      lv_timer_t *timer = lv_timer_create(hidePopupNoWifiBig, 1000, NULL);
    } else {
      // Serial.println("pindah ke fingerprint");
      lv_scr_load(objects.fingerprint_page); 
    }
  }
}

static void button_config_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke config");
    lv_scr_load(objects.config_page);
  }
}

static void password_event_handler(lv_event_t *e) {
  int btn_index = lv_keyboard_get_selected_btn(objects.keyboard_pass);
  String password = lv_textarea_get_text(objects.input_password);
  // Serial.println(btn_index);
  // Serial.println(password);
  if (btn_index == 11) {
    if (password == "236148") {
      // Serial.print("password benar");
      lv_scr_load(objects.menu_page);
    } else {
      // Serial.print("pasword salah");
      lv_obj_clear_flag(objects.popup_wrong, LV_OBJ_FLAG_HIDDEN);
      lv_timer_t *timer = lv_timer_create(hidePopupWrong, 2000, NULL);
    }
    lv_textarea_set_text(objects.input_password, "");
  }
}

void hidePopupWrong(lv_timer_t *timer) {
  fingerstate = true;
  lv_obj_add_flag(objects.popup_wrong, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}

void hidePopupNoWifiBig(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_no_wifi_big, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}


//Fingerprint page
static void button_backtofingerprint_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke fingerprint");
    lv_scr_load(objects.fingerprint_page); 
  }
}

static void button_register_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke register");
    lv_scr_load(objects.input_id_reg); 
  }
}

static void button_delete_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke delete");
    lv_scr_load(objects.delete_page); 
  }
}


//Register page
static void button_backtoreg_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke idreg");
    lv_scr_load(objects.input_id_reg); 
  }
}

static void register_event_handler(lv_event_t *e) {
  int btn_index = lv_keyboard_get_selected_btn(objects.keyboard_reg);
  if (btn_index == 11) {
    String id = lv_textarea_get_text(objects.input_reg);
    showPopupInputIdasLoading();
    get_table_data(id);
    lv_textarea_set_text(objects.input_reg, "");
  }
}

static void get_table_data(String id) {
  // Get data from the server
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;

    // Build server path
    String serverPath = serverName + "/" + id;

    // Connect to the server
    http.begin(serverPath);
    http.addHeader("Authorization", authToken);

    // Send HTTP GET request
    int httpResponseCode = http.GET();

    if (httpResponseCode > 0) {
      // Serial.print("HTTP Response code: ");
      // Serial.println(httpResponseCode);

      String payload = http.getString();
      // Serial.println("Received Payload:");
      // Serial.println(payload);

      // Parse JSON payload
      StaticJsonDocument<1024> doc;  // Adjust buffer size as needed
      DeserializationError error = deserializeJson(doc, payload);

      if (!error) {
        // Extract members array
        String name = doc["users"]["name"].as<String>();
        String category = doc["users"]["category_name"].as<String>();
        finger_id = doc["users"]["id_fingerprint"].as<int>();
        if (name != "null") {
          // Insert ID and Name into the table
          lv_table_set_cell_value(objects.tabel_user, 1, 0, id.c_str());
          lv_table_set_cell_value(objects.tabel_user, 1, 1, name.c_str());
          lv_table_set_cell_value(objects.tabel_user, 1, 2, category.c_str());
          lv_scr_load(objects.register_page);
          lv_obj_add_flag(objects.popup_input_id, LV_OBJ_FLAG_HIDDEN);
        } else {
          showPopupInputId("Member Not Found!");
        }

      } else {
        // Serial.print("JSON Deserialization failed: ");
        // Serial.println(error.c_str());
        showPopupInputId("Invalid server response!");
      }
    } else {
      // Serial.print("Error code: ");
      // Serial.println(httpResponseCode);
      showPopupInputId("Server Error!");
    }

    // Free resources
    http.end();
  } else {
    // Serial.println("WiFi Disconnected");
    showPopupInputId("WiFi Disconnected!");
  }
}

uint8_t getFingerprintEnroll(int id) {
  uint8_t p = finger.getImage();

  lv_tick_inc(millis() - lastTick);  // Update the tick timer.
  lastTick = millis();
  // Serial.print("Waiting for valid finger to enroll as #");
  // Serial.println(id);
  while (p != FINGERPRINT_OK) {
    lv_tick_inc(millis() - lastTick);  // Update the tick timer.
    lastTick = millis();
    lv_timer_handler();
    return p;
  }

  // OK success!

  p = finger.image2Tz(1);
  switch (p) {
    case FINGERPRINT_OK:
      // Serial.println("Image converted");
      break;
    case FINGERPRINT_IMAGEMESS:
      // Serial.println("Image too messy");
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      // Serial.println("Communication error");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      // Serial.println("Could not find fingerprint features");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      // Serial.println("Could not find fingerprint features");
      return p;
    default:
      // Serial.println("Unknown error");
      return p;
  }

  // Serial.println("Remove finger");
  lv_tick_inc(millis() - lastTick);  // Update the tick timer.
  lastTick = millis();
  lv_timer_handler();
  showPopupRegister("Remove finger then place the same finger again", true, false);

  bool loop = true;
  while (loop) {
    lv_timer_handler();
    if (millis() - lastTick >= 2000) {
      loop = false;
    }
  }

  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
  }
  // Serial.print("ID ");
  // Serial.println(id);
  p = -1;
  // Serial.println("Place same finger again");

  unsigned long startTime = millis();
  while (p != FINGERPRINT_OK) {
    lv_tick_inc(millis() - lastTick);  // Update the tick timer.
    lastTick = millis();
    lv_timer_handler();

    p = finger.getImage();

    // Periksa waktu yang telah berlalu
    if (millis() - startTime > 6000) {
      // Serial.println("Timeout reached: 6 seconds");
      showPopupRegister("Operation timed out. Try again.", false, false);
      break;  // Keluar dari loop setelah 6 detik
    }

    switch (p) {
      case FINGERPRINT_OK:
        // Serial.println("Image taken");
        break;
      case FINGERPRINT_NOFINGER:
        // Serial.print(".");
        break;
      case FINGERPRINT_PACKETRECIEVEERR:
        // Serial.println("Communication error");
        break;
      case FINGERPRINT_IMAGEFAIL:
        // Serial.println("Imaging error");
        break;
      default:
        // Serial.println("Unknown error");
        break;
    }
    delay(50);
  }
  // OK success!

  p = finger.image2Tz(2);
  switch (p) {
    case FINGERPRINT_OK:
      // Serial.println("Image converted");
      break;
    case FINGERPRINT_IMAGEMESS:
      // Serial.println("Image too messy");
      showPopupRegister("Fingerprint too messy", false, false);
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      // Serial.println("Communication error");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      // Serial.println("Could not find fingerprint features");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      // Serial.println("Could not find fingerprint features");
      return p;
    default:
      // Serial.println("Unknown error");
      return p;
  }

  // OK converted!
  // Serial.print("Creating model for #");
  // Serial.println(id);

  p = finger.createModel();
  if (p == FINGERPRINT_OK) {
    // Serial.println("Prints matched!");
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    // Serial.println("Communication error");
    return p;
  } else if (p == FINGERPRINT_ENROLLMISMATCH) {
    // Serial.println("Fingerprints did not match");
    return p;
  } else {
    // Serial.println("Unknown error");
    return p;
  }

  // Serial.print("ID ");
  // Serial.println(id);
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    // Serial.println("Stored!");
    showPopupRegister("Stored", false, true);
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    // Serial.println("Communication error");
    return p;
  } else if (p == FINGERPRINT_BADLOCATION) {
    // Serial.println("Could not store in that location");
    return p;
  } else if (p == FINGERPRINT_FLASHERR) {
    // Serial.println("Error writing to flash");
    return p;
  } else {
    // Serial.println("Unknown error");
    return p;
  }

  return true;
}

void showPopupInputIdasLoading() {
  lv_label_set_text(objects.text_input_id, "Please wait..");
  lv_obj_add_flag(objects.icon_inputid_gagal, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.icon_inputid_loading, LV_OBJ_FLAG_HIDDEN);
  lv_obj_clear_flag(objects.popup_input_id, LV_OBJ_FLAG_HIDDEN);
}

void showPopupInputId(const char* info) {
  lv_label_set_text(objects.text_input_id, info);
  lv_obj_clear_flag(objects.icon_inputid_gagal, LV_OBJ_FLAG_HIDDEN);
  lv_obj_add_flag(objects.icon_inputid_loading, LV_OBJ_FLAG_HIDDEN);
  lv_timer_t *timer = lv_timer_create(hidePopupInputId, 3000, NULL);
}

void hidePopupInputId(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_input_id, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}

void showPopupRegister(const char* status, bool success, bool exit) {
  if (success) {
    lv_label_set_text(objects.text_register_status, status);
    lv_obj_add_flag(objects.icon_gagal_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_berhasil_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_register, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupRegister, 3000, NULL);
  } if (exit) {
    lv_label_set_text(objects.text_register_status, status);
    lv_obj_add_flag(objects.icon_gagal_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_berhasil_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_register, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupRegisterExit, 3000, NULL);
  } else {
    lv_label_set_text(objects.text_register_status, status);
    lv_obj_clear_flag(objects.icon_gagal_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_berhasil_register, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_register, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupRegister, 3000, NULL);
  }
}

void hidePopupRegister(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_register, LV_OBJ_FLAG_HIDDEN); 
  lv_timer_del(timer);
}

void hidePopupRegisterExit(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_register, LV_OBJ_FLAG_HIDDEN); 
  lv_timer_del(timer);
  lv_scr_load(objects.input_id_reg);
}


//Delete Page
static void delete_event_handler(lv_event_t *e) {
  int btn_index = lv_keyboard_get_selected_btn(objects.keyboard_del);
  if (btn_index == 11) {
    const char *id_str = lv_textarea_get_text(objects.input_del);
    // int id = atoi(id_str);
    deleteFingerprint(atoi(id_str));
    lv_textarea_set_text(objects.input_del, "");
  }
}

uint8_t deleteFingerprint(int id) {
  uint8_t p = finger.deleteModel(id);

  if (p == FINGERPRINT_OK) {
    // Serial.println("Deleted!");
    showPopupDelete("Deleted",true);
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    // Serial.println("Communication error");
    showPopupDelete("Communication error",false);
  } else if (p == FINGERPRINT_BADLOCATION) {
    // Serial.println("Could not delete in that location");
    showPopupDelete("Could not delete in that location",false);
  } else if (p == FINGERPRINT_FLASHERR) {
    // Serial.println("Error writing to flash");
    showPopupDelete("Error writing to flash",false);
  } else {
    // Serial.print("Unknown error: 0x");
    // Serial.println(p, HEX);
    showPopupDelete("Unknown error",false);
  }
  return p;
}

void showPopupDelete(const char* info, bool status) {
  if (status) {
    lv_label_set_text(objects.text_delete_status, info);
    lv_obj_clear_flag(objects.icon_berhasil_delete, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_gagal_delete, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_delete, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupDelete, 3000, NULL);
  } else {
    lv_label_set_text(objects.text_delete_status, info);
    lv_obj_add_flag(objects.icon_berhasil_delete, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_gagal_delete, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_delete, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupDelete, 3000, NULL);
  }
}

void hidePopupDelete(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_delete, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}


//Config page
static void button_backtoconfig_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke config");
    lv_scr_load(objects.config_page);
    if (apstatus) {
      server.stop();
      WiFi.softAPdisconnect(true);
      wifitimeouttimer = 0;
      wificooldowntimer = 0;
      apstatus = false;
    }
  }
}

static void button_wifi_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke wifi");
    lv_scr_load(objects.wifi_page);
    startap = true;
    apstatus = true;
  }
}

static void button_about_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    // Serial.println("pindah ke about");
    lv_scr_load(objects.about_page);
  }
}

static void button_reset_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    lv_obj_clear_flag(objects.notif_verif_reset, LV_OBJ_FLAG_HIDDEN);
  }
}

static void button_pin_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    refreshfingerlogin();
    lv_scr_load(objects.finger_login_page);
  }
}

static void button_verif_cancel_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    lv_obj_add_flag(objects.notif_verif_reset, LV_OBJ_FLAG_HIDDEN);
  }
}

static void button_verif_reset_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    lv_obj_clear_flag(objects.notif_verif_reset, LV_OBJ_FLAG_HIDDEN);
    resetFingerprint();
  }
}

uint8_t resetFingerprint() {
  uint8_t p = finger.emptyDatabase();

  if (p == FINGERPRINT_OK) {
    // Serial.println("Deleted!");
    // status = "All data has been erased";
    showPopupReset("All data has been erased",true);
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    // Serial.println("Communication error");
    // status = "Communication error";
    showPopupReset("Communication error",false);
  } else if (p == FINGERPRINT_BADLOCATION) {
    // Serial.println("Could not delete in that location");
    // status = "Could not delete in that location";
    showPopupReset("Could not delete in that location",false);
  } else if (p == FINGERPRINT_FLASHERR) {
    // Serial.println("Error writing to flash");
    // status = "Error writing to flash";
    showPopupReset("Error writing to flash",false);
  } else {
    // Serial.print("Unknown error: 0x");
    // Serial.println(p, HEX);
    // status = "Unknown error";
    showPopupReset("Unknown error",false);
  }
  return p;
}

void showPopupReset(const char* info, bool status) {
  if (status) {
    lv_label_set_text(objects.text_reset_status, info);
    lv_obj_clear_flag(objects.icon_berhasil_reset, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_gagal_reset, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_reset, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupReset, 3000, NULL);
  } else {
    lv_label_set_text(objects.text_reset_status, info);
    lv_obj_add_flag(objects.icon_berhasil_reset, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_gagal_reset, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.popup_reset, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupReset, 3000, NULL);
  }
}

void hidePopupReset(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_reset, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}



//WiFi page
void showPopupWifiStatus(bool success) {
  if (success) {
    lv_label_set_text(objects.text_wifi_status, "Connected");
    lv_obj_clear_flag(objects.popup_wifi_status, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_gagal_wifi, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_berhasil_wifi, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupWifiStatusQuit, 4000, NULL);
  } else {
    lv_label_set_text(objects.text_wifi_status, "Connection Timeout");
    lv_obj_clear_flag(objects.popup_wifi_status, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_gagal_wifi, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_berhasil_wifi, LV_OBJ_FLAG_HIDDEN);
    lv_timer_t *timer = lv_timer_create(hidePopupWifiStatus, 4000, NULL);
  }
}

void hidePopupWifiStatus(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_wifi_status, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}

void hidePopupWifiStatusQuit(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_wifi_status, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
  lv_scr_load(objects.config_page);
}

//Finger Login Page
static void button_backtofingerlogin_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    refreshfingerlogin();
    lv_scr_load(objects.finger_login_page);
  }
}

static void button_add_login_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    if (strcmp(id1, "") == '0' && strcmp(id2, "") == '0') {
      showPopupFingerLoginPage("Max ID",false);
    } else {
      lv_scr_load(objects.input_id_login);
    }
  }
}

static void button_btnid1_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    if (strcmp(id1, btn1) == 0) {
      strcpy(id1, "");
      EEPROM.writeString(64, id1);
      EEPROM.commit();
      refreshfingerlogin();
      showPopupFingerLoginPage("Deleted",true);
    } else {
      strcpy(id2, "");
      EEPROM.writeString(67, id2);
      EEPROM.commit();
      refreshfingerlogin();
      showPopupFingerLoginPage("Deleted",true);
    }
  }
}

static void button_btnid2_event_handler(lv_event_t *e) {
  if (lv_event_get_code(e) == LV_EVENT_CLICKED) {
    if (strcmp(id1, btn2) == 0) {
      strcpy(id1, "");
      EEPROM.writeString(64, id1);
      EEPROM.commit();
      refreshfingerlogin();
      showPopupFingerLoginPage("Deleted",true);
    } else {
      strcpy(id2, "");
      EEPROM.writeString(67, id2);
      EEPROM.commit();
      refreshfingerlogin();
      showPopupFingerLoginPage("Deleted",true);
    }
  }
}

void showPopupFingerLoginPage(const char* status, bool success) {
  lv_label_set_text(objects.text_fingerlogin, status);
  if (success) {
    lv_obj_clear_flag(objects.icon_berhasil_fingerlogin, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_gagal_fingerlogin, LV_OBJ_FLAG_HIDDEN);
  } else {
    lv_obj_add_flag(objects.icon_berhasil_fingerlogin, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_gagal_fingerlogin, LV_OBJ_FLAG_HIDDEN);
  }
  lv_obj_clear_flag(objects.popup_fingerlogin_notif, LV_OBJ_FLAG_HIDDEN);
  lv_timer_t *timer = lv_timer_create(hidePopupFingerLoginNotif, 3000, NULL);
}

void hidePopupFingerLoginNotif(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_fingerlogin_notif, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}

void refreshfingerlogin() {
  EEPROM.readString(64).toCharArray(id1, 3);
  EEPROM.readString(67).toCharArray(id2, 3);
  if(strcmp(id1, "") != 0 && strcmp(id2, "") == 0) {
    lv_obj_clear_flag(objects.login_id1, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.login_id2, LV_OBJ_FLAG_HIDDEN);
    lv_label_set_text(objects.text_id1, id1);
    strcpy(btn1, id1);
  } else if(strcmp(id1, "") == 0 && strcmp(id2, "") != 0) {
    lv_obj_clear_flag(objects.login_id1, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.login_id2, LV_OBJ_FLAG_HIDDEN);
    lv_label_set_text(objects.text_id1, id2);
    strcpy(btn1, id2);
  } else if(strcmp(id1, "") != 0  && strcmp(id2, "") != 0) {
    lv_obj_clear_flag(objects.login_id1, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.login_id2, LV_OBJ_FLAG_HIDDEN);
    lv_label_set_text(objects.text_id1, id1);
    lv_label_set_text(objects.text_id2, id2);
    strcpy(btn1, id1);
    strcpy(btn2, id2);
  } else {
    lv_obj_add_flag(objects.login_id1, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.login_id2, LV_OBJ_FLAG_HIDDEN);
    strcpy(btn1, "");
    strcpy(btn2, "");
  }
}

//InputIDLogin
static void inputlogin_event_handler(lv_event_t *e) {
  int btn_index = lv_keyboard_get_selected_btn(objects.keyboard_login);
  String login = lv_textarea_get_text(objects.input_login);
  if (btn_index == 11) {
    if (login.length() > 3) {
      showPopupInputFingerLogin("Max ID lenght is 3", false, false);
    } else {
      if(strcmp(id1, "") != 0 && strcmp(id2, "") == 0) {
        strcpy(id2, login.c_str());
        EEPROM.writeString(67, id2);
        EEPROM.commit();
        showPopupInputFingerLogin("ID Saved", true, true);
      } else if(strcmp(id1, "") == 0 && strcmp(id2, "") != 0) {
        strcpy(id1, login.c_str());
        EEPROM.writeString(64, id1);
        EEPROM.commit();
        showPopupInputFingerLogin("ID Saved", true, true);
      } else if(strcmp(id1, "") != 0 && strcmp(id2, "") == 0) {
        showPopupInputFingerLogin("Max ID", false, true);
      } else {
        strcpy(id1, login.c_str());
        EEPROM.writeString(64, id1);
        EEPROM.commit();
        showPopupInputFingerLogin("ID Saved", true, true);
      }
    }
    lv_textarea_set_text(objects.input_login, "");
  }
}

void showPopupInputFingerLogin(const char* status, bool success, bool quit) {
  lv_label_set_text(objects.text_delete_status_1, status);
  if (success) {
    lv_obj_clear_flag(objects.icon_berhasil_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
    lv_obj_add_flag(objects.icon_gagal_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
  } else {
    lv_obj_add_flag(objects.icon_berhasil_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
    lv_obj_clear_flag(objects.icon_gagal_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
  }
  lv_obj_clear_flag(objects.popup_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
  if (quit) {
    lv_timer_t *timer = lv_timer_create(hidePopupInputFingerLoginQuit, 3000, NULL);
  } else {
    lv_timer_t *timer = lv_timer_create(hidePopupInputFingerLogin, 3000, NULL);
  }
}

void hidePopupInputFingerLogin(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
}

void hidePopupInputFingerLoginQuit(lv_timer_t *timer) {
  lv_obj_add_flag(objects.popup_inputfingerlogin, LV_OBJ_FLAG_HIDDEN);
  lv_timer_del(timer);
  refreshfingerlogin();
  lv_scr_load(objects.finger_login_page);
}




//Web server handler
void handleRoot() {
  String webpage;
  webpage += "<!DOCTYPE html>";
  webpage += "<html>";
    webpage += "<head>";
      webpage += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
      webpage += "<title>Absensi Enuma WiFi Config</title>";
      webpage += "<style>";
        webpage += "* {box-sizing: border-box; }";
        webpage += "input[type='text'],select,textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;}";
        webpage += "input[type='password'],select,textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;}";
        webpage += "label { padding: 12px 12px 12px 0; display: inline-block;}";
        webpage += "input[type='submit'] { width: 100%; background-color: #04aa6d; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; float: right; }";
        webpage += "input[type='submit']:hover { background-color: #45a049; }";
        webpage += ".container { border-radius: 5px; background-color: #f2f2f2; padding: 20px; }";
        webpage += ".col-25 { float: left; width: 25%; margin-top: 6px; }";
        webpage += ".col-75 { float: left; width: 75%; margin-top: 6px; }";
        webpage += ".row::after { content: ''; display: table; clear: both; }";
//        webpage += "@media screen and (max-width: 600px) { .col-25, .col-75, input[type='submit'] { margin-top: 0; } }";
      webpage += "</style>";
    webpage += "</head>";
    webpage += "<body>";
      webpage += "<div class='container' style='max-width: 800px; margin: auto'>";
        webpage += "<h2>Absensi Enuma WiFi Config</h2>";
        webpage += "<form action='/connect' method='POST'>";
          webpage += "<div class='row'>";
            webpage += "<div class='col-25'><label>SSID</label></div>";
            webpage += "<div class='col-75'><input type='text' name='ssid' placeholder='ssid' /></div>";
          webpage += "</div>";
          webpage += "<div class='row'>";
            webpage += "<div class='col-25'><label>Password</label></div>";
            webpage += "<div class='col-75'><input type='password' name='password' placeholder='password' /></div>";
          webpage += "</div><br/>";
          webpage += "<div class='row'><input type='submit' value='Connect' /></div>";
        webpage += "</form>";
      webpage += "</div>";
    webpage += "</body>";
  webpage += "</html>";
  server.send(200, "text/html", webpage);
}

void handleConnect() {
  String webpage;
  webpage += "<!DOCTYPE html><html>";
  webpage += "<head>";
    webpage += "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    webpage += "<title>Device Connect</title>";
    webpage += "<style>";
      webpage += "* { box-sizing: border-box; }";
      webpage += "a { background-color: #04aa6d; color: white; padding: 10px 10px; border: none; border-radius: 4px; cursor: pointer; float: left; text-decoration: none; }";
      webpage += "a:hover { background-color: #45a049; }";
      webpage += ".container { border-radius: 5px; background-color: #f2f2f2; padding: 20px; }";
      webpage += ".row::after { content: ''; display: table; clear: both; }";
    webpage += "</style>";
  webpage += "</head>";
  webpage += "<body>";
    webpage += "<div class='container' style='max-width: 800px; margin: auto'>";
      webpage += "<h2>Device Status</h2><p>Check ur device for connection status</p><p>Go back if your device is failed to connect</p>";
      webpage += "<div class='row'><a href='/'>Go back</a></div>";
    webpage += "</div>";
  webpage += "</body>";
  webpage += "</html>";
  server.send(200, "text/html", webpage);
  if (server.args() > 0) {
    for ( uint8_t i = 0; i < server.args(); i++ ) {
      if (server.argName(i) == "ssid") {
        Serial.println(server.arg(i));
        strcpy(stassid, server.arg(i).c_str());
        // stassid = server.arg(i);
      }
      if (server.argName(i) == "password") {
        Serial.println(server.arg(i));
        strcpy(stapsk, server.arg(i).c_str());
      }
    }
    apstartconnect = true;
  }
}



// wifi connection handler
void wificonnectionhandler() {
  if (WiFi.status() != WL_CONNECTED) {
    if(startwifi == true) {
      //wifi start with eeprom
      // char ssid[32] = {0};
      // char pass[32] = {0};
      loadCredential(stassid,stapsk);
      Serial.print("ssid: ");
      Serial.println(stassid);
      Serial.print("password: ");
      Serial.println(stapsk);
      WiFi.begin(stassid,stapsk);
      wificonnecting = true;
      startwifi = false;
      Serial.println("Connecting WiFi");
      String status = "Connecting..";
      lv_label_set_text(objects.text_nowifi_notif, "Connecting..");
      lv_obj_add_flag(objects.icon_no_wifi, LV_OBJ_FLAG_HIDDEN);
      lv_obj_clear_flag(objects.icon_nowifi_loading, LV_OBJ_FLAG_HIDDEN);
    } else if(startwifi == false && wificonnecting == false && cooldownconnecting == false) {
      startwifi = true;
    } else {
      if (wificonnecting == true) {
        if (WiFi.status() != WL_CONNECTED) {
          // Serial.print(".");
          //timeout
          wifitimeouttimer += 1;
          if (wifitimeouttimer > wifitimeout) {
            wificonnecting = false;
            cooldownconnecting = true;
            // Serial.println("Failed to Connect WiFi");
            WiFi.disconnect();
            wifitimeouttimer = 0;
            // String status = "No Wifi Connected";
            lv_label_set_text(objects.text_nowifi_notif, "No Wifi Connected");
            lv_obj_clear_flag(objects.icon_no_wifi, LV_OBJ_FLAG_HIDDEN);
            lv_obj_add_flag(objects.icon_nowifi_loading, LV_OBJ_FLAG_HIDDEN);
          }
        }
      } else if(cooldownconnecting == true) {
        wificooldowntimer += 1;
        if (wificooldowntimer > wificooldown) {
          startwifi = true;
          wificooldowntimer = 0;
          cooldownconnecting = false;
        }
      }
    }
  } else {
    if (wificonnecting == true) {
      wificonnecting = false;
      if (firstboot) {
        sntp_set_sync_interval(12 * 60 * 60 * 1000UL);  // sync clock every 12 hours
        sntp_set_time_sync_notification_cb(cbSyncTime);  // set a Callback function for time synchronization notification
        configTime(0, 0, ntpServer);

        setTimezone(timezone);
        firstboot = false;
      }
    }
  }
}

//AP setting for wifi
void Wifisettinghandler() {
  if(startap == true) {
    wifitimeouttimer = 0;
    wificooldowntimer = 0;
    WiFi.disconnect();
    WiFi.softAP(APSSID, APPSK);
    Serial.println("starting AP");
    server.begin();
    Serial.println("Starting Webserver");

    startap = false;
  } else {
    server.handleClient();
    if (apstartconnect) {
      WiFi.begin(stassid,stapsk);
      Serial.println("Connecting..");
      apconnecting = true;
      apstartconnect = false;
      lv_obj_clear_flag(objects.popup_processing_1, LV_OBJ_FLAG_HIDDEN);
    }
    if(apconnecting) {
      wifitimeouttimer += 1;
      if (WiFi.status() != WL_CONNECTED) {
        Serial.print(".");
        wifitimeouttimer += 1;
        if (wifitimeouttimer > wifitimeout) {
          apconnecting = false;
          Serial.println("Failed to Connect WiFi");
          WiFi.disconnect();
          wifitimeouttimer = 0;
          lv_obj_add_flag(objects.popup_processing_1, LV_OBJ_FLAG_HIDDEN);
          showPopupWifiStatus(false);
        }
      } else {
        apconnecting = false;
        Serial.println("WiFi Connected!");
        server.stop();
        WiFi.softAPdisconnect(true);
        saveCredential(stassid,stapsk);
        lv_obj_add_flag(objects.popup_processing_1, LV_OBJ_FLAG_HIDDEN);
        showPopupWifiStatus(true);
        apstatus = false;
        wifitimeouttimer = 0;
        wificooldowntimer = 0;
        if (firstboot) {
          sntp_set_sync_interval(12 * 60 * 60 * 1000UL);  // sync clock every 12 hours
          sntp_set_time_sync_notification_cb(cbSyncTime);  // set a Callback function for time synchronization notification
          configTime(0, 0, ntpServer);

          setTimezone(timezone);
          firstboot = false;
        }
      }
    }
  }
}



// Core function
void setup() {
  Serial.begin(115200);
  mySerial.begin(57600, SERIAL_8N1, 21, 22);

  EEPROM.begin(EEPROM_SIZE);

  //fingerprint start
  Serial.println("\nAdafruit Fingerprint sensor test");
  if (finger.verifyPassword()) {
    Serial.println("Found fingerprint sensor!");
  } else {
    Serial.println("Did not find fingerprint sensor :(");
  }

  EEPROM.readString(64).toCharArray(id1, 3);
  EEPROM.readString(67).toCharArray(id2, 3);

  tft.begin();
  tft.setRotation(1);

  // Initialize LVGL
  lv_init();

  // Create display buffer
  draw_buf = new uint8_t[DRAW_BUF_SIZE];
  lv_display_t *disp = lv_tft_espi_create(SCREEN_HEIGHT, SCREEN_WIDTH, draw_buf, DRAW_BUF_SIZE);
  lv_display_set_rotation(disp, LV_DISPLAY_ROTATION_90);

  // Initialize LVGL input device (Touchscreen)
  lv_indev_t *indev = lv_indev_create();
  lv_indev_set_type(indev, LV_INDEV_TYPE_POINTER);
  lv_indev_set_read_cb(indev, touchscreen_read);

  Serial.println("LVGL Setup Completed.");
  delay(500);

  // Initialize EEZ Studio GUI
  ui_init();

  //UI setup

  //Main Page
  lv_obj_add_event_cb(objects.button_menu, button_menu_event_handler, LV_EVENT_CLICKED, NULL);
  //Login Page
  lv_obj_add_event_cb(objects.button_back_1, button_backtomain_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.keyboard_pass, password_event_handler, LV_EVENT_CLICKED, NULL);
  //Menu Page
  lv_obj_add_event_cb(objects.button_back_2, button_backtomain_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_fingerprint, button_fingerprint_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_config, button_config_event_handler, LV_EVENT_CLICKED, NULL);
  //Fingerprint Page
  lv_obj_add_event_cb(objects.button_back_3, button_backtomenu_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_register, button_register_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_delete, button_delete_event_handler, LV_EVENT_CLICKED, NULL);
  //Register Page
  lv_obj_add_event_cb(objects.button_back_4, button_backtofingerprint_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.keyboard_reg, register_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_back_5, button_backtoreg_event_handler, LV_EVENT_CLICKED, NULL);
  //Delete Page
  lv_obj_add_event_cb(objects.button_back_6, button_backtofingerprint_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.keyboard_del, delete_event_handler, LV_EVENT_CLICKED, NULL);
  //Config Page
  lv_obj_add_event_cb(objects.button_back_7, button_backtomenu_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_wifi, button_wifi_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_pin, button_pin_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_info, button_about_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_reset, button_reset_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_verif_cancel, button_verif_cancel_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.button_verif_reset, button_verif_reset_event_handler, LV_EVENT_CLICKED, NULL);
  //Wifi Page
  lv_obj_add_event_cb(objects.button_back_8, button_backtoconfig_event_handler, LV_EVENT_CLICKED, NULL);
  //About Page
  lv_obj_add_event_cb(objects.button_back_9, button_backtoconfig_event_handler, LV_EVENT_CLICKED, NULL);
  //Login Finger Page
  lv_obj_add_event_cb(objects.button_back_10, button_backtoconfig_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.btn_add_login, button_add_login_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.btn_id1, button_btnid1_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.btn_id2, button_btnid2_event_handler, LV_EVENT_CLICKED, NULL);
  //Input ID Login
  lv_obj_add_event_cb(objects.button_back_11, button_backtofingerlogin_event_handler, LV_EVENT_CLICKED, NULL);
  lv_obj_add_event_cb(objects.keyboard_login, inputlogin_event_handler, LV_EVENT_CLICKED, NULL);

  lv_table_set_col_cnt(objects.tabel_user, 3);
  lv_table_set_col_width(objects.tabel_user, 0, 60);
  lv_table_set_col_width(objects.tabel_user, 1, 210);
  lv_table_set_col_width(objects.tabel_user, 2, 170);

  lv_table_set_row_count(objects.tabel_user, 2);
  lv_obj_set_style_text_font(objects.tabel_user, &lv_font_montserrat_18, LV_PART_ITEMS | LV_STATE_DEFAULT);

  lv_table_set_cell_value(objects.tabel_user, 0, 0, "ID");
  lv_table_set_cell_value(objects.tabel_user, 0, 1, "Name");
  lv_table_set_cell_value(objects.tabel_user, 0, 2, "Category");

  //webserver setup
  server.on("/", handleRoot);
  server.on("/connect",handleConnect);

  WiFi.setHostname("EnumaAbsen");

  startwifi = true;
}

void loop() {
  //lvgl script
  lv_tick_inc(millis() - lastTick);
  lastTick = millis();
  lv_timer_handler();

  //assign timer 1ms
  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis >= 1) {
    previousMillis = currentMillis;
    if (apstatus) {
      Wifisettinghandler();
    } else {
      wificonnectionhandler();
    }
  }

  //register handler
  if (lv_scr_act() == objects.register_page) {
    getFingerprintEnroll(finger_id);
  }

  // reading fingerprint with delay(50) without affecting UI
  unsigned long currentMillis2 = millis();
  if (currentMillis2 - previousMillis2 >= 50) {
    previousMillis2 = currentMillis2;
    if (lv_scr_act() == objects.main && fingerstate) {
      getFingerprintID();
    } else if (lv_scr_act() == objects.clock_page && fingerstate) {
      getFingerprintID();
    } else if (lv_scr_act() == objects.login_page && fingerstate) {
      getFingerprintLogin();
    }
  }

  // clock and lvgl handler 
  unsigned long currentMillis3 = millis();
  if (currentMillis3 - previousMillis3 >= 1000) {
    previousMillis3 = currentMillis3;
    if (clockstatus) {
      if (firstboot == false) {
        update_clock_labels();
      }
      if (startclock) {
        lv_scr_load(objects.clock_page);
        startclock = false;
      }
    } else {
      if (lv_scr_act() == objects.main && fingerstate && WiFi.status() == WL_CONNECTED) {
        screencooldowntimer += 1;
        if (screencooldowntimer >= screencooldown) {
          clockstatus = true;
          startclock = true;
        }
      }
    }
  }

  //popup not connected handler
  if (WiFi.status() == WL_CONNECTED) {
    if (connectionpopup) {
      hidePopupWifiNotConnected();
      connectionpopup = false;
    }
  } else {
    if (connectionpopup == false) {
      showPopupWifiNotConnected();
      connectionpopup = true;
      screencooldowntimer = 0;
      if (clockstatus == true) {
        clockstatus = false;
        lv_scr_load(objects.main);
      }
    }
  }
}