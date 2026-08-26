export default function searchPalette() {
    return {
        query: '',
        results: { projects: [], tasks: [], members: [] },
        highlightedIndex: 0,
        debounceTimer: null,
        requestId: 0,

        openPalette() {
            this.query = '';
            this.results = { projects: [], tasks: [], members: [] };
            this.highlightedIndex = 0;
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'search-palette' }));
        },

        onInput() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.fetchResults(), 200);
        },

        async fetchResults() {
            if (this.query.trim().length < 2) {
                this.results = { projects: [], tasks: [], members: [] };
                this.highlightedIndex = 0;
                return;
            }

            const requestId = ++this.requestId;

            const response = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            if (requestId !== this.requestId) {
                return;
            }

            this.results = data;
            this.highlightedIndex = 0;
        },

        flatResults() {
            return [
                ...this.results.projects.map((p) => ({ label: p.name, url: `/projects/${p.id}` })),
                ...this.results.tasks.map((t) => ({ label: t.title, url: `/tasks/${t.id}` })),
                ...this.results.members.map((m) => ({ label: m.name, url: `/members#member-${m.id}` })),
            ];
        },

        moveHighlight(delta) {
            const total = this.flatResults().length;
            if (total === 0) return;
            this.highlightedIndex = (this.highlightedIndex + delta + total) % total;
        },

        goToHighlighted() {
            const item = this.flatResults()[this.highlightedIndex];
            if (item) window.location.href = item.url;
        },

        isHighlighted(url) {
            const item = this.flatResults()[this.highlightedIndex];
            return item ? item.url === url : false;
        },
    };
}
