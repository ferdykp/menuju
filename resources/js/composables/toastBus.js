import { reactive } from 'vue';

const state = reactive({ items: [] });
let nextId = 1;

export function useToast() {
    function remove(id) {
        const index = state.items.findIndex((item) => item.id === id);
        if (index !== -1) {
            window.clearTimeout(state.items[index].timer);
            state.items.splice(index, 1);
        }
    }

    function success(message, duration = 5000) {
        const id = nextId++;
        const toast = { id, message, duration, timer: null };
        state.items.push(toast);
        toast.timer = window.setTimeout(() => remove(id), duration);
        while (state.items.length > 4) remove(state.items[0].id);
        return id;
    }

    return { toasts: state.items, success, remove };
}
