import './bootstrap';

import * as Popper from "@popperjs/core"
window.Popper = Popper;

import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import 'laravel-datatables-vite';

import select2 from 'select2';

import $ from 'jquery';
import 'summernote/dist/summernote-lite.css';
import 'summernote/dist/summernote-lite.js';


window.jQuery = window.$ = $;

// Bridge Bootstrap components to jQuery
$.fn.modal = function(option) {
    return this.each(function() {
        const $this = $(this);
        let data = $this.data('bs.modal');

        if (!data) {
            data = new bootstrap.Modal(this, typeof option === 'object' && option);
            $this.data('bs.modal', data);
        }

        if (typeof option === 'string') {
            if (typeof data[option] === 'undefined') {
                throw new TypeError(`No method named "${option}"`);
            }
            data[option].call(data);
        } else if (option && option.show !== false) {
            data.show();
        }
    });
};

select2();

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
