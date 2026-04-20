#ifndef EEZ_LVGL_UI_SCREENS_H
#define EEZ_LVGL_UI_SCREENS_H

#include <lvgl.h>

#ifdef __cplusplus
extern "C" {
#endif

// Screens

enum ScreensEnum {
    _SCREEN_ID_FIRST = 1,
    SCREEN_ID_MAIN = 1,
    SCREEN_ID_LOGIN_PAGE = 2,
    SCREEN_ID_MENU_PAGE = 3,
    SCREEN_ID_FINGERPRINT_PAGE = 4,
    SCREEN_ID_INPUT_ID_REG = 5,
    SCREEN_ID_REGISTER_PAGE = 6,
    SCREEN_ID_DELETE_PAGE = 7,
    SCREEN_ID_CONFIG_PAGE = 8,
    SCREEN_ID_WIFI_PAGE = 9,
    SCREEN_ID_ABOUT_PAGE = 10,
    SCREEN_ID_CLOCK_PAGE = 11,
    SCREEN_ID_FINGER_LOGIN_PAGE = 12,
    SCREEN_ID_INPUT_ID_LOGIN = 13,
    _SCREEN_ID_LAST = 13
};

typedef struct _objects_t {
    lv_obj_t *main;
    lv_obj_t *login_page;
    lv_obj_t *menu_page;
    lv_obj_t *fingerprint_page;
    lv_obj_t *input_id_reg;
    lv_obj_t *register_page;
    lv_obj_t *delete_page;
    lv_obj_t *config_page;
    lv_obj_t *wifi_page;
    lv_obj_t *about_page;
    lv_obj_t *clock_page;
    lv_obj_t *finger_login_page;
    lv_obj_t *input_id_login;
    lv_obj_t *obj0;
    lv_obj_t *button_menu;
    lv_obj_t *no_wifi_notif;
    lv_obj_t *icon_nowifi_loading;
    lv_obj_t *text_nowifi_notif;
    lv_obj_t *icon_no_wifi;
    lv_obj_t *popup_attendance;
    lv_obj_t *text_nama;
    lv_obj_t *text_welcome;
    lv_obj_t *icon_berhasil;
    lv_obj_t *icon_gagal;
    lv_obj_t *popup_processing;
    lv_obj_t *obj1;
    lv_obj_t *obj2;
    lv_obj_t *button_back_1;
    lv_obj_t *input_password;
    lv_obj_t *keyboard_pass;
    lv_obj_t *no_wifi_notif_1;
    lv_obj_t *popup_wrong;
    lv_obj_t *obj3;
    lv_obj_t *obj4;
    lv_obj_t *button_fingerprint;
    lv_obj_t *button_config;
    lv_obj_t *no_wifi_notif_2;
    lv_obj_t *button_back_2;
    lv_obj_t *popup_no_wifi_big;
    lv_obj_t *obj5;
    lv_obj_t *obj6;
    lv_obj_t *button_back_3;
    lv_obj_t *button_register;
    lv_obj_t *button_delete;
    lv_obj_t *obj7;
    lv_obj_t *obj8;
    lv_obj_t *button_back_4;
    lv_obj_t *input_reg;
    lv_obj_t *keyboard_reg;
    lv_obj_t *popup_input_id;
    lv_obj_t *icon_inputid_loading;
    lv_obj_t *icon_inputid_gagal;
    lv_obj_t *text_input_id;
    lv_obj_t *obj9;
    lv_obj_t *obj10;
    lv_obj_t *button_back_5;
    lv_obj_t *tabel_user;
    lv_obj_t *popup_register;
    lv_obj_t *text_register_status;
    lv_obj_t *icon_berhasil_register;
    lv_obj_t *icon_gagal_register;
    lv_obj_t *obj11;
    lv_obj_t *obj12;
    lv_obj_t *button_back_6;
    lv_obj_t *input_del;
    lv_obj_t *keyboard_del;
    lv_obj_t *popup_delete;
    lv_obj_t *text_delete_status;
    lv_obj_t *icon_berhasil_delete;
    lv_obj_t *icon_gagal_delete;
    lv_obj_t *obj13;
    lv_obj_t *obj14;
    lv_obj_t *button_back_7;
    lv_obj_t *button_wifi;
    lv_obj_t *obj15;
    lv_obj_t *button_reset;
    lv_obj_t *obj16;
    lv_obj_t *button_pin;
    lv_obj_t *obj17;
    lv_obj_t *button_info;
    lv_obj_t *obj18;
    lv_obj_t *no_wifi_notif_3;
    lv_obj_t *notif_verif_reset;
    lv_obj_t *button_verif_cancel;
    lv_obj_t *button_verif_reset;
    lv_obj_t *popup_reset;
    lv_obj_t *text_reset_status;
    lv_obj_t *icon_berhasil_reset;
    lv_obj_t *icon_gagal_reset;
    lv_obj_t *obj19;
    lv_obj_t *obj20;
    lv_obj_t *button_back_8;
    lv_obj_t *popup_processing_1;
    lv_obj_t *popup_wifi_status;
    lv_obj_t *text_wifi_status;
    lv_obj_t *icon_berhasil_wifi;
    lv_obj_t *icon_gagal_wifi;
    lv_obj_t *obj21;
    lv_obj_t *obj22;
    lv_obj_t *button_back_9;
    lv_obj_t *text_date;
    lv_obj_t *text_clock;
    lv_obj_t *obj23;
    lv_obj_t *obj24;
    lv_obj_t *button_back_10;
    lv_obj_t *login_id1;
    lv_obj_t *text_id1;
    lv_obj_t *btn_id1;
    lv_obj_t *login_id2;
    lv_obj_t *text_id2;
    lv_obj_t *btn_id2;
    lv_obj_t *btn_add_login;
    lv_obj_t *popup_fingerlogin_notif;
    lv_obj_t *text_fingerlogin;
    lv_obj_t *icon_berhasil_fingerlogin;
    lv_obj_t *icon_gagal_fingerlogin;
    lv_obj_t *obj25;
    lv_obj_t *obj26;
    lv_obj_t *button_back_11;
    lv_obj_t *input_login;
    lv_obj_t *keyboard_login;
    lv_obj_t *popup_inputfingerlogin;
    lv_obj_t *text_delete_status_1;
    lv_obj_t *icon_berhasil_inputfingerlogin;
    lv_obj_t *icon_gagal_inputfingerlogin;
} objects_t;

extern objects_t objects;

void create_screen_main();
void tick_screen_main();

void create_screen_login_page();
void tick_screen_login_page();

void create_screen_menu_page();
void tick_screen_menu_page();

void create_screen_fingerprint_page();
void tick_screen_fingerprint_page();

void create_screen_input_id_reg();
void tick_screen_input_id_reg();

void create_screen_register_page();
void tick_screen_register_page();

void create_screen_delete_page();
void tick_screen_delete_page();

void create_screen_config_page();
void tick_screen_config_page();

void create_screen_wifi_page();
void tick_screen_wifi_page();

void create_screen_about_page();
void tick_screen_about_page();

void create_screen_clock_page();
void tick_screen_clock_page();

void create_screen_finger_login_page();
void tick_screen_finger_login_page();

void create_screen_input_id_login();
void tick_screen_input_id_login();

void tick_screen_by_id(enum ScreensEnum screenId);
void tick_screen(int screen_index);

void create_screens();

#ifdef __cplusplus
}
#endif

#endif /*EEZ_LVGL_UI_SCREENS_H*/