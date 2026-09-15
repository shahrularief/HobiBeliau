const cards = [
  { name: 'Ember Warden', type: 'Creature', element: 'Fire', rarity: 'Legendary', set: 'First Dawn', stock: 3, art: 'ember', symbol: '✦' },
  { name: 'Moonveil Oracle', type: 'Creature', element: 'Celestial', rarity: 'Epic', set: 'First Dawn', stock: 7, art: 'moon', symbol: '◒' },
  { name: 'Verdant Colossus', type: 'Creature', element: 'Nature', rarity: 'Epic', set: 'Wild Origins', stock: 5, art: 'verdant', symbol: '✳' },
  { name: 'Starfall', type: 'Spell', element: 'Celestial', rarity: 'Rare', set: 'First Dawn', stock: 12, art: 'star', symbol: '✧' },
  { name: 'Rift Compass', type: 'Artifact', element: 'Arcane', rarity: 'Rare', set: 'Lost Relics', stock: 4, art: 'rift', symbol: '⌖' },
  { name: 'Tidal Messenger', type: 'Creature', element: 'Water', rarity: 'Uncommon', set: 'Wild Origins', stock: 9, art: 'tidal', symbol: '≈' },
  { name: 'Echo Lantern', type: 'Artifact', element: 'Spirit', rarity: 'Epic', set: 'Lost Relics', stock: 2, art: 'echo', symbol: '☼' },
  { name: 'Thornbound', type: 'Spell', element: 'Nature', rarity: 'Uncommon', set: 'Wild Origins', stock: 16, art: 'thorn', symbol: '❋' }
];

const grid = document.querySelector('#card-grid');
const search = document.querySelector('#search');
const sort = document.querySelector('#sort');
const resultCount = document.querySelector('#result-count');
const emptyState = document.querySelector('#empty-state');
const filters = [...document.querySelectorAll('[data-filter]')];
let activeFilter = 'all';

document.querySelector('#hero-unique').textContent = String(cards.length).padStart(2, '0');
document.querySelector('#hero-total').textContent = String(cards.reduce((total, card) => total + card.stock, 0)).padStart(2, '0');

function render() {
  const query = search.value.trim().toLocaleLowerCase();
  const visible = cards.filter(card =>
    (activeFilter === 'all' || card.type === activeFilter) &&
    [card.name, card.type, card.element, card.rarity, card.set].some(value => value.toLocaleLowerCase().includes(query))
  );
  if (sort.value === 'name') visible.sort((a, b) => a.name.localeCompare(b.name));
  if (sort.value === 'stock') visible.sort((a, b) => b.stock - a.stock);
  grid.innerHTML = visible.map((card, index) => `
    <article class="inventory-card">
      <div class="card-art art-${card.art}" aria-hidden="true"><span class="art-orbit"></span><span class="art-symbol">${card.symbol}</span><span class="art-index">AV / ${String(cards.indexOf(card) + 1).padStart(3, '0')}</span></div>
      <div class="card-meta"><span>${card.type} / ${card.element}</span><span>${card.rarity}</span></div>
      <h3>${card.name}</h3>
      <div class="card-bottom"><span>${card.set}</span><strong>${card.stock} in stock</strong></div>
    </article>`).join('');
  resultCount.textContent = `${visible.length} ${visible.length === 1 ? 'card' : 'cards'} found`;
  emptyState.hidden = visible.length !== 0;
}

search.addEventListener('input', render);
sort.addEventListener('change', render);
filters.forEach(button => button.addEventListener('click', () => {
  activeFilter = button.dataset.filter;
  filters.forEach(item => {
    const selected = item === button;
    item.classList.toggle('active', selected);
    item.setAttribute('aria-pressed', String(selected));
  });
  render();
}));
render();
