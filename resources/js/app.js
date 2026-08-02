import './bootstrap';
import './driver-native-bridge';
import { registerPwa } from './pwa';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

registerPwa();

Alpine.data('toastManager', () => ({
    toasts: [],
    add(message, type = 'success') {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, message, type, show: false });
        this.$nextTick(() => {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.show = true;
        });
        setTimeout(() => this.remove(id), 4000);
    },
    remove(id) {
        const t = this.toasts.find(t => t.id === id);
        if (t) {
            t.show = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 350);
        }
    },
}));

Livewire.start();
