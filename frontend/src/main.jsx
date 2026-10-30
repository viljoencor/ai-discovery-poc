import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import './styles.css';

const prompts = [
  'Pet-friendly beachfront in Hermanus for 4 guests with parking, under R2,000 per night',
  'Cape Town for 4 guests, parking and sea view, under R2,200',
  'Knysna for 4 people, pet-friendly, wifi and braai, under R1,800',
  'Hermanus for 4 guests under R2,000 from 2026-11-12 to 2026-11-15',
];
const amenityNames = { parking:'Parking', wifi:'Wi-Fi', pool:'Pool', braai:'Braai', sea_view:'Sea view', beachfront:'Beachfront' };
const formatter = new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR', maximumFractionDigits: 0 });

function FormattedFilters({ intent }) {
  const entries = [
    ['Location', intent.destination],
    ['Guests', intent.guests],
    ['Budget', intent.max_price_zar ? `${formatter.format(intent.max_price_zar)}/night` : null],
    ['Pets', intent.pets_allowed === null ? null : intent.pets_allowed ? 'Allowed' : 'Not allowed'],
    ['Check-in', intent.check_in],
    ['Check-out', intent.check_out],
  ].filter(([, value]) => value !== null && value !== undefined);
  return (
    <div className="filter-row">
      {entries.map(([label, value]) => <div className="filter-pill" key={label}><span>{label}</span><strong>{value}</strong></div>)}
      {intent.amenities?.map(a => <div className="filter-pill" key={a}><span>Feature</span><strong>{amenityNames[a] || a}</strong></div>)}
      {!entries.length && !intent.amenities?.length && <p className="muted">No filters confidently extracted. Try another example.</p>}
    </div>
  );
}

function ListingCard({ item, index }) {
  const color = ['water', 'sand', 'forest', 'stone'][index % 4];
  return (
    <article className="listing">
      <div className={`listing-illustration ${color}`} aria-hidden="true"><div className="sun"/><div className="roof"/><div className="house"/></div>
      <div className="listing-content">
        <div className="listing-location">{item.city} · Demo property</div>
        <h3>{item.name}</h3>
        <p>{item.description}</p>
        <div className="listing-meta"><span>Up to {item.guest_capacity} guests</span><span>{item.pets_allowed ? 'Pets welcome' : 'No pets'}</span></div>
        <div className="amenities">{item.amenities.map(feature => <span key={feature}>{amenityNames[feature] || feature}</span>)}</div>
        <div className="listing-footer"><div><strong>{formatter.format(item.price_per_night_zar)}</strong><span> indicative / night</span></div><span className="sample-flag">SAMPLE</span></div>
      </div>
    </article>
  );
}

function App() {
  const [query, setQuery] = useState(prompts[0]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [response, setResponse] = useState(null);
  const [showDetails, setShowDetails] = useState(false);

  async function search(e) {
    e?.preventDefault();
    setLoading(true); setError(''); setResponse(null);
    try {
      const res = await fetch('/api/ai-search', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ query }),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.message || 'Request failed');
      setResponse(json);
    } catch (err) {
      setError(err.message || 'Unable to connect to the API. Ensure the Docker services are running.');
    } finally { setLoading(false); }
  }
  return (
    <div className="site">
      <header className="topbar"><div className="wrap topbar-content"><div className="brand-mark"><span className="logo">S<span className="logo-dot">.</span></span><span className="brand-copy">RestFinder <b>AI</b><small>ENGINEERING CONCEPT</small></span></div><div className="header-right"><span className="status-dot"/> Unofficial demo <span className="divider-dot">·</span> Inspired POC</div></div></header>
      <main>
        <section className="hero">
          <div className="wrap hero-layout"><div className="eyebrow">TRAVEL SEARCH, REIMAGINED</div><h1>Discover your next stay.<br/><em>Just describe it.</em></h1><p className="hero-sub">Natural language meets trusted property data. AI understands your request. The application verifies what matches.</p>
            <form className="search-panel" onSubmit={search}><label htmlFor="query">Tell us what you're looking for</label><div className="search-field"><textarea id="query" value={query} onChange={e=>setQuery(e.target.value)} maxLength={500} rows={3} placeholder="Where would you like to stay?" required minLength={5}/><button type="submit" disabled={loading}>{loading ? 'Searching…' : 'Find stays'} <span aria-hidden="true">↗</span></button></div><div className="search-foot"><span>No live listings or booking access</span><span>{query.length}/500 characters</span></div></form>
            <div className="examples"><span>TRY AN EXAMPLE</span><div>{prompts.map((p,i) => <button key={p} type="button" onClick={()=>{setQuery(p);setResponse(null);setError('')}}>{['Pet-friendly Hermanus','Cape Town sea view','Knysna retreat','Date-based search'][i]}</button>)}</div></div>
          </div>
        </section>
        <section className="results-section wrap">
          {error && <div role="alert" className="error-box"><strong>Unable to complete search</strong><p>{error}</p></div>}
          {!response && !error && <div className="empty-state"><div className="empty-icon">⌕</div><h2>Start with a simple question</h2><p>Use a suggested request or write your own. Results come from a sample property database, not the AI model.</p></div>}
          {response && <>
            <div className="results-heading"><div><div className="eyebrow dark">INTERPRETED REQUEST</div><h2>We understood your search</h2></div><span className="mode-label">{response.mode === 'mock' ? 'Deterministic demo parser' : 'Amazon Bedrock model'}</span></div>
            <FormattedFilters intent={response.intent}/>
            <div className="results-heading listing-title"><div><div className="eyebrow dark">MATCHING ACCOMMODATION</div><h2>{response.result_count} sample {response.result_count === 1 ? 'stay' : 'stays'} found</h2></div><span className="muted">Sorted by indicative nightly price</span></div>
            {response.results.length ? <div className="listing-grid">{response.results.map((item, i) => <ListingCard key={item.id} item={item} index={i}/>)}</div> : <div className="no-results"><h3>No demo properties match all filters</h3><p>Try increasing the budget, removing an amenity, or changing the destination.</p></div>}
            <p className="disclaimer">{response.disclaimer} {response.availability_checked ? 'Sample date blocks were checked.' : 'Dates were not provided, so availability was not evaluated.'}</p>
            <button className="technical-toggle" onClick={()=>setShowDetails(v=>!v)} type="button">{showDetails ? 'Hide' : 'Show'} technical execution details <span>{showDetails?'−':'+'}</span></button>
            {showDetails && <pre className="json-panel">{JSON.stringify({request_id: response.request_id, mode: response.mode, intent: response.intent, availability_checked: response.availability_checked}, null, 2)}</pre>}
          </>}
        </section>
        <section className="architecture-strip"><div className="wrap"><h2>Engineered for trustworthy results</h2><div className="architecture-flow"><span>React interface</span><b>→</b><span>Laravel API</span><b>→</b><span>Python AI intent</span><b>→</b><span>Laravel queries</span><b>→</b><span>MariaDB records</span></div><p>Only verified application data is presented. The language model never executes SQL or confirms a booking.</p></div></section>
      </main>
      <footer><div className="wrap">Unofficial engineering proof of concept</div></footer>
    </div>
  );
}

createRoot(document.getElementById('root')).render(<App/>);
