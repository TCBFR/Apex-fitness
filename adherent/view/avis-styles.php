<style>
  .review-layout { display:grid; grid-template-columns:minmax(0, 1fr) 330px; gap:22px; margin-top:20px; align-items:start; }
  .review-form { display:grid; gap:18px; }
  .review-label { display:grid; gap:8px; color:var(--text-2); font-weight:600; }
  .review-select, .review-textarea { width:100%; border:1px solid var(--border-2); border-radius:10px; background:#0d1218; color:#fff; font:inherit; padding:12px 14px; }
  .review-select { height:46px; }
  .review-textarea { min-height:140px; resize:vertical; }
  .review-select:focus, .review-textarea:focus { outline:2px solid var(--neon); outline-offset:2px; }
  .review-meta { display:flex; justify-content:space-between; gap:12px; color:var(--muted); font-size:13px; }
  .review-error { padding:12px 14px; border:1px solid rgba(255,77,94,.5); border-radius:10px; color:#ff9ba5; background:rgba(255,77,94,.08); }
  .review-stars { color:var(--neon); letter-spacing:4px; font-size:24px; }
  .review-comment { margin-top:14px; color:var(--text-2); white-space:pre-wrap; overflow-wrap:anywhere; }
  .review-reply { margin-top:20px; padding:16px; border-left:3px solid var(--neon); background:rgba(204,255,0,.06); border-radius:0 10px 10px 0; }
  .review-reply p { margin-top:6px; color:var(--text-2); }
  @media(max-width:900px){.review-layout{grid-template-columns:1fr}}
</style>
