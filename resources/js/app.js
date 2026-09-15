import './bootstrap';

const registerCardCatalog = () => {
    Alpine.data('cardCatalog', () => ({
        query: '', cards: [], page: 1, busy: false, message: '',
        async search(page) {
            if (this.busy) return;
            if (this.query.trim().length < 2) { this.message = 'Enter at least two characters.'; return; }
            this.busy = true; this.message = 'Searching…';
            try {
                const response = await fetch('/seller/catalog/cards?' + new URLSearchParams({q: this.query.trim(), page}), {headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error();
                this.cards = await response.json(); this.page = page;
                this.message = this.cards.length ? 'Select the correct card printing.' : 'No cards found. Try another name or enter details manually.';
            } catch { this.cards = []; this.message = 'Card search is unavailable. Try again or enter details manually.'; }
            finally { this.busy = false; }
        },
        async select(id) {
            if (this.busy) return;
            this.busy = true;
            try {
                const response = await fetch('/seller/catalog/cards/' + encodeURIComponent(id), {headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error();
                const card = await response.json();
                for (const [field, value] of Object.entries({title: card.name, game: 'Pokémon', set_name: card.set})) {
                    const input = this.$root.closest('form').elements.namedItem(field);
                    input.value = value;
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                    input.dispatchEvent(new Event('change', {bubbles: true}));
                }
                this.$root.closest('form').elements.namedItem('tcgdex_id').value = card.id;
                this.message = 'Selected ' + card.name + ' · ' + card.set + ' · #' + card.number + '. Check the details below.';
            } catch { this.message = 'Could not load that card. Try again or enter details manually.'; }
            finally { this.busy = false; }
        }
    }));
};
if (window.Alpine) registerCardCatalog();
else document.addEventListener('alpine:init', registerCardCatalog);
