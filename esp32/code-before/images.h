#ifndef EEZ_LVGL_UI_IMAGES_H
#define EEZ_LVGL_UI_IMAGES_H

#include <lvgl.h>

#ifdef __cplusplus
extern "C" {
#endif

extern const lv_img_dsc_t img_loading;
extern const lv_img_dsc_t img_no_wifi;
extern const lv_img_dsc_t img_icon_fail;
extern const lv_img_dsc_t img_icon_success;
extern const lv_img_dsc_t img_fingerprint;
extern const lv_img_dsc_t img_cog;
extern const lv_img_dsc_t img_pen;
extern const lv_img_dsc_t img_trash;
extern const lv_img_dsc_t img_fingerprint_black;
extern const lv_img_dsc_t img_keyboard;
extern const lv_img_dsc_t img_reload;
extern const lv_img_dsc_t img_wifi;
extern const lv_img_dsc_t img_info;
extern const lv_img_dsc_t img_circle_exclamation;
extern const lv_img_dsc_t img_wifi_white;
extern const lv_img_dsc_t img_info_white;
extern const lv_img_dsc_t img_no_wifi_big;
extern const lv_img_dsc_t img_background;
extern const lv_img_dsc_t img_logo;
extern const lv_img_dsc_t img_smalllogo;

#ifndef EXT_IMG_DESC_T
#define EXT_IMG_DESC_T
typedef struct _ext_img_desc_t {
    const char *name;
    const lv_img_dsc_t *img_dsc;
} ext_img_desc_t;
#endif

extern const ext_img_desc_t images[20];

#ifdef __cplusplus
}
#endif

#endif /*EEZ_LVGL_UI_IMAGES_H*/