import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { plugin as VueTippy } from 'vue-tippy'
import 'tippy.js/dist/tippy.css' // optional for styling


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueTippy, {
                directive: 'tippy',
                component: 'tippy',
                defaultProps: {
                    placement: 'top',
                    theme: 'dark',
                },
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
