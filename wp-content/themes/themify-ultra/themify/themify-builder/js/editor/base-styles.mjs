'use strict';
export class BaseStyles {
    static get_expand(label, options) {
        return {
            type: 'expand',
            label: label,
            options: options
        };
    }
    static get_tab(options, fullwidth = false, cl = '') {
        const opt = {
            type: 'tabs',
            options: options
        };
        if (fullwidth === true) {
            if (cl !== '') {
                cl += ' tb_tabs_fullwidth';
            } else {
                cl = 'tb_tabs_fullwidth';
            }
        }
        if (cl !== '') {
            opt.class = cl;
        }
        return opt;
    }
    static get_color(selector = '', id = '', label = null, prop = 'color', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        if (prop === null) {
            prop = 'color';
        }
        const color = {
            id: id,
            type: 'color',
            prop: prop,
            selector: selector
        };
        if (label !== null) {
            color.label = label;
        }
        if (state === 'h' || state === 'hover') {
            color.h = true;
        }
        return color;
    }
    static get_font_family(selector = '', id = 'font_family', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'font_select',
            prop: 'font-family',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }
    static get_seperator(label = false) {
        const opt = {
            type: 'separator'
        };
        if (label !== false && label!=='f') {
            opt.label = label;
        }
        return opt;
    }
    static get_font_size(selector = '', id = 'font_size', label = '', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'fontSize',
            selector: selector,
            prop: 'font-size'
        };
        if (label !== '') {
            res.label = label;
        }
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static get_line_height(selector = '', id = 'line_height', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'lineHeight',
            selector: selector,
            prop: 'line-height'
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static get_letter_spacing(selector = '', id = 'letter_spacing', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'letterSpace',
            selector: selector,
            prop: 'letter-spacing'
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }
    static get_text_align(selector = '', id = 'text_align', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            label: 't_a',
            type: 'icon_radio',
            aligment: true,
            prop: 'text-align',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static get_text_transform(selector = '', id = 'text_transform', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            label: 't_t',
            type: 'icon_radio',
            text_transform: true,
            prop: 'text-transform',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static get_text_decoration(selector = '', id = 'text_decoration', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'icon_radio',
            label: 't_d',
            text_decoration: true,
            prop: 'text-decoration',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static get_font_style(selector = '', id = 'font_style', id2 = 'font_weight', state = '') {
        if (state !== '') {
            id += '_' + state;
            id2 += '_' + state;
        }
        const res = {
            id: id,
            id2: id2,
            type: 'fontStyle',
            prop: 'font-style',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }

        return res;
    }

    static  get_image(selector = '', id = 'background_image', colorId = 'background_color', repeatId = 'background_repeat', posId = 'background_position', state = '') {
        if (state !== '') {
            id += '_' + state;
            if (colorId !== '') {
                colorId += '_' + state;
            }
            if (repeatId !== '') {
                repeatId += '_' + state;
            }
            if (posId !== '') {
                posId += '_' + state;
            }
        }
        const res = {
            id: id,
            type: 'imageGradient',
            prop: 'background-image',
            selector: selector,
            origId: id,
            colorId: colorId,
            repeatId: repeatId,
            posId: posId
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // CSS Filters
    static  get_blend(selector = '', id = 'bl_m', state = '', filters_id = 'css_f') {
        if (state !== '') {
            id += '_' + state;
            filters_id += '_' + state;
        }
        const res = {
            id: filters_id,
            mid: id,
            type: 'filters',
            prop: 'filter',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_repeat(selector = '', id = 'background_repeat', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            label: 'b_r',
            type: 'select',
            repeat: true,
            prop: 'background-mode',
            selector: selector,
            wrap_class: 'tb_group_element_image tb_image_options'
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_position(selector = '', id = 'background_position', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'position_box',
            prop: 'background-position',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_padding(selector = '', id = 'padding', state = '') {
        if (id === '') {
            id = 'padding';
        }
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'padding',
            prop: 'padding',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_margin(selector = '', id = 'margin', state = '') {
        if (id === '') {
            id = 'margin';
        }
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'margin',
            prop: 'margin',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_margin_top_bottom_opposity(selector = '', topId = 'margin-top', bottomId = 'margin-bottom', state = '') {
        if (state !== '') {
            topId += '_' + state;
            bottomId += '_' + state;
        }
        const res = {
            topId: topId,
            bottomId: bottomId,
            type: 'margin_opposity',
            prop: '',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_border(selector = '', id = 'border', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'border',
            prop: 'border',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_outline(selector = '', id = 'o', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'outline',
            prop: 'outline',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_aspect_ratio(selector = '', id = 'asp', state = '') {

        if (state !== '') {
            id += '_' + state;
        }

        const res = {
            id: id,
            type: 'aspectRatio',
            prop: 'aspect-ratio',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_multi_columns_count(selector = '', id = 'column', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id + '_count',
            type: 'multiColumns',
            prop: 'column-count',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    static  get_color_type(selector = '', state = '', id = '', solid_id = '', gradient_id = '') {
        if (state !== '') {
            if (id === '') {
                id = 'f_c_t';
            }
            if (solid_id === '') {
                solid_id = 'f_c';
            }
            if (gradient_id === '') {
                gradient_id = 'f_g_c';
            }
            id += '_' + state;
            solid_id += '_' + state;
            gradient_id += '_' + state;
        } else {
            if (id === '') {
                id = 'font_color_type';
            }
            if (solid_id === '') {
                solid_id = 'font_color';
            }
            if (gradient_id === '') {
                gradient_id = 'font_gradient_color';
            }
        }

        const res = {
            id: id,
            type: 'fontColor',
            selector: selector,
            prop: 'radio',
            s: solid_id,
            g: gradient_id
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }
    // Get Rounded Corners
    static  get_border_radius(selector = '', id = 'b_ra', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'border_radius',
            prop: 'border-radius',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // Get Box Shadow
    static  get_box_shadow(selector = '', id = 'b_sh', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'box_shadow',
            prop: 'box-shadow',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // Get Text Shadow
    static  get_text_shadow(selector = '', id = 'text-shadow', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'text_shadow',
            prop: 'text-shadow',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // Get z-index
    static  get_zindex(selector = '', id = 'zi') {
        return {
            id: id,
            selector: selector,
            prop: 'z-index',
            type: 'zIndex'
        };
    }

    static  get_width(selector = '', id = 'width', state = '') {

        if (state !== '') {
            id += '_' + state;
        }

        const res = {
            id: id,
            type: 'width',
            prop: 'width',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // Get Height Options plus Auto Height
    static  get_height(selector = '', id = 'ht', state = '', minH = '', maxH = '') {

        if (state !== '') {
            id += '_' + state;
        }

        const res = {
            id: id,
            type: 'height',
            prop: 'height',
            selector: selector
        };
        if (minH !== '') {
            res.minid = minH;
        }
        if (maxH !== '') {
            res.maxid = maxH;
        }
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // Get CSS Position
    static  get_css_position(selector = '', id = 'po', state = '') {
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'position',
            prop: 'position',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }

    // CSS Display
    static  get_display(selector = '', id = 'disp') {
        const va_id = id + '_va';
        return [
            {
                id: id,
                label: 'disp',
                type: 'select',
                prop: 'display',
                selector: selector,
                binding: {
                    empty: {hide: va_id},
                    block: {hide: va_id},
                    none: {hide: va_id},
                    'inline-block': {show: va_id}
                },
                display: true
            },
            {
                id: va_id,
                label: 'valign',
                type: 'select',
                prop: 'vertical-align',
                selector: selector,
                origID: id,
                va_display: true
            }
        ];
    }
    // Get transform
    static  get_transform(selector = '', id = 'tr', state = '') {
        if (state !== '') {
            id += '-' + state;
        }
        const res = {
            id: id,
            type: 'transform',
            prop: 'transform',
            selector: selector
        };
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }
    static get_gap(selector = '', id = 'gap', prop = 'gap', state = '', units='', label = '') {
        if (id === '') {
            id = 'gap';
        }
        if (state !== '') {
            id += '_' + state;
        }
        const res = {
            id: id,
            type: 'gap',
            prop: prop,
            selector: selector
        };
        if (units) {
            res.units=units;
        }
        if(label!=='' && label!==undefined){
            res.label=label;
        }
        if (state === 'h' || state === 'hover') {
            res.h = true;
        }
        return res;
    }
    static get_column_gap(selector = '', id = 'cgap', state = '', units = '', label = '') {
        if (id === '') {
            id = 'cgap';
        }
        return this.get_gap(selector, id, 'column-gap', state, units, label);
    }

    static get_row_gap(selector = '', id = 'rgap', state = '', units = '', label = '') {
        if (id === '') {
            id = 'rgap';
        }
        return this.get_gap(selector, id, 'row-gap', state, units, label);
    }
    static get_grid_flow(selector = '',id='gdr'){
        return {
            id: id,
            type: 'grid_flow',
            prop: 'grid-auto-flow',
            selector: selector
        };
    }
    
    static get_justify_content(selector = '',id='jc'){
        return {
            id: id,
            label:'jc',
            type: 'icon_radio',
            prop: 'justify-content',
            default:'inherit',
            no_toggle:1,
            jc:true,
            selector: selector
        };
    }
    static get_justify_items(selector = '',id='ji'){
        return {
            id: id,
            label:'ji',
            type: 'icon_radio',
            prop: 'justify-items',
            default:'inherit',
            no_toggle:1,
            ji:true,
            selector: selector
        };
    }
    static get_align_items(selector = '',id='ai'){
        return {
            id: id,
            label:'ai',
            type: 'icon_radio',
            prop: 'align-items',
            default:'inherit',
            no_toggle:1,
            ai:true,
            selector: selector
        };
    }
    static get_align_content(selector = '',id='ac'){
        return {
            id: id,
            label:'ac',
            type: 'icon_radio',
            prop: 'align-content',
            default:'inherit',
            no_toggle:1,
            ac:true,
            selector: selector
        };
    }
    static get_align_self(selector = '',id='as'){
        return {
            id: id,
            label:'as',
            type: 'icon_radio',
            prop: 'align-self',
            default:'auto',
            no_toggle:1,
            as:true,
            selector: selector
        };
    }
    static get_justify_self(selector = '',id='js'){
        return {
            id: id,
            label:'js',
            type: 'icon_radio',
            prop: 'justify-self',
            default:'auto',
            no_toggle:1,
            js:true,
            selector: selector
        };
    }
    static get_frame_tabs(selector = '') {
        return this.get_tab(
                {
                    top: this.get_frame_props(selector),
                    bottom: this.get_frame_props(selector, 'bottom'),
                    left: this.get_frame_props(selector, 'left'),
                    right: this.get_frame_props(selector, 'right')
                }
        );
    }

    static get_frame_props(selector = '', id = 'top') {
        return [
            {
                id: id + '-frame_type',
                type: 'radio',
                options: [
                    /**
                     * @note the value in this option is prefixed with id, this is to ensure option_js works properly
                     */
                    {value: id + '-presets', name: 'presets'},
                    {value: id + '-custom', name: 'cus'}
                ],
                prop: 'frame-custom',
                wrap_class: 'tb_frame',
                /**
                 * the second selector is for themes with Builder Section Scrolling feature
                 * @ref #7241
                 */
                selector: selector + '>.tb_row_frame_wrap .tb_row_frame_' + id,
                option_js: true
            },
            {
                id: id + '-frame_layout',
                type: 'frame',
                prop: 'frame',
                wrap_class: 'frame_tabs tb_group_element_' + id + '-presets',
                selector: selector + '>.tb_row_frame_wrap .tb_row_frame_' + id
            },
            {
                id: id + '-frame_custom',
                type: 'image',
                label: '',
                class: 'tb_frame',
                wrap_class: 'tb_group_element_' + id + '-custom'
            },
            {
                id: id + '-frame_color',
                type: 'color',
                label: 'c',
                class: 'tb_frame small',
                wrap_class: 'tb_group_element_' + id + '-presets'
            },
            {
                type: 'multi',
                label: 'dim',
                options: [
                    {
                        id: id + '-frame_width',
                        type: 'range',
                        class: 'tb_frame xsmall',
                        label: 'w',
                        select_class: 'tb_frame_unit',
                        units: {
                            '%': {
                                max: 1000
                            },
                            px: {
                                max: 10000
                            },
                            em: {
                                max: 50
                            }
                        }
                    },
                    {
                        id: id + '-frame_height',
                        type: 'range',
                        class: 'tb_frame xsmall',
                        label: 'ht',
                        select_class: 'tb_frame_unit',
                        units: {
                            '%': {
                                max: 1000
                            },
                            px: {
                                max: 10000
                            },
                            em: {
                                max: 50
                            }
                        }
                    },
                    {
                        id: id + '-frame_repeat',
                        type: 'range',
                        label: 'r',
                        class: 'tb_frame'
                    }
                ]
            },
            {
                type: 'multi',
                label: 'sh',
                options: [
                    {
                        id: id + '-frame_sh_x',
                        type: 'range',
                        class: 'tb_frame xsmall',
                        tooltip: 'loffs'
                    },
                    {
                        id: id + '-frame_sh_y',
                        type: 'range',
                        class: 'tb_frame xsmall',
                        tooltip: 'toffs'
                    },
                    {
                        id: id + '-frame_sh_b',
                        type: 'range',
                        class: 'tb_frame',
                        tooltip: 'blur'
                    },
                    {
                        id: id + '-frame_sh_c',
                        type: 'color',
                        label: false,
                        class: 'tb_frame',
                        tooltip: 'color'
                    }
                ]
            },
            {
                type: 'multi',
                label: 'animation',
                options: [
                    {
                        id: id + '-frame_ani_dur',
                        type: 'range',
                        units: {
                            '': {
                                increment: .1
                            }
                        },
                        class: 'tb_frame xsmall',
                        tooltip: 'dur'
                    },
                    {
                        id: id + '-frame_ani_rev',
                        type: 'toggle_switch',
                        options: {
                            on: {name: '1', value: 'rev'},
                            off: {name: '', value: 'rev'}
                        },
                        wrap_class: 'tb_frame'
                    }
                ]
            },
            {
                id: id + '-frame_location',
                label: '',
                type: 'select',
                is_responsive: false,
                class: 'tb_frame',
                options: {
                    in_bellow: 'belowcont',
                    in_front: 'abovecont'
                }
            }
        ];
    }
    
	static module_title_custom_style() {
		return [
			// Background
			this.get_expand('bg',[
				this.get_tab({
					n: [
							this.get_color('.module .module-title', 'background_color_module_title', 'bg_c', 'background-color')
                    ],
					h: [
							this.get_color('.module .module-title', 'bg_c_m_t', 'bg_c', 'background-color', 'h')
                    ]
                })
            ]),
			// Font
			this.get_expand('f', [
				this.get_tab({
					n : [
							this.get_font_family('.module .module-title', 'font_family_module_title'),
							this.get_color('.module .module-title', 'font_color_module_title'),
							this.get_font_size('.module .module-title', 'font_size_module_title'),
							this.get_line_height('.module .module-title', 'line_height_module_title'),
							this.get_text_align('.module .module-title', 'text_align_module_title'),
							this.get_text_shadow('.module .module-title', 't_sh_m_t')
                    ],
					h : [
							this.get_font_family('.module .module-title', 'f_f_m_t', 'h'),
							this.get_color('.module .module-title', 'f_c_m_t', null, null, 'h'),
							this.get_font_size('.module .module-title', 'f_s_m_t', '', 'h'),
							this.get_text_shadow('.module .module-title', 't_sh_m_t', 'h')
                    ]
                })
            ])
        ];
	}
}


