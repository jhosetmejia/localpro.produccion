<style>
.ap-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:1.8rem 1.6rem;position:relative;overflow:hidden;margin-bottom:1rem}
.ap-hero::after{content:"";position:absolute;width:320px;height:320px;border-radius:50%;border:46px solid rgba(166,243,74,.12);right:-100px;top:-120px}
.ap-hero small{color:#9fe3b0;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.75rem}
.ap-hero h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:900;line-height:1.08;margin:.35rem 0 .5rem;max-width:22ch;position:relative;z-index:1}
.ap-hero h1 em{font-style:normal;color:#a6f34a}
.ap-hero p{color:#c9d3cc;max-width:54ch;font-weight:500;margin:0;position:relative;z-index:1}
.ap-barra{height:8px;border-radius:99px;background:#e3ebe6;overflow:hidden}
.ap-barra i{display:block;height:100%;background:linear-gradient(90deg,#a3f04a,#16b84e)}
.ap-hero .ap-barra{background:rgba(255,255,255,.12);max-width:360px;margin-top:1rem;position:relative;z-index:1}
.ap-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:.9rem}
.ap-card{background:#fff;border:1.5px solid #e3ebe6;border-radius:24px;padding:1.15rem 1.2rem;display:flex;flex-direction:column;gap:.5rem;text-decoration:none;color:#0b0d0c;transition:transform .15s,box-shadow .15s,border-color .15s;position:relative}
.ap-card:hover{transform:translateY(-3px);box-shadow:0 18px 34px -22px rgba(0,0,0,.4);border-color:#bfeccb;color:#0b0d0c}
.ap-card .ic{width:50px;height:50px;border-radius:16px;display:grid;place-items:center;font-size:1.35rem;color:#fff}
.ap-card h3{font-size:1.08rem;font-weight:900;margin:0}
.ap-card p{color:#55605a;font-size:.9rem;font-weight:500;margin:0;flex:1}
.ap-card .meta{display:flex;justify-content:space-between;font-size:.78rem;font-weight:800;color:#6b7570}
.ap-card .sello{position:absolute;top:14px;right:14px;font-size:.7rem;font-weight:900;border-radius:999px;padding:.2rem .6rem;background:#dcfce7;color:#11702f}
.ap-card .sello.d{background:#fff4e5;color:#b45309}
.ap-lec{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;margin-bottom:.7rem;overflow:hidden}
.ap-lec summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:.8rem;padding:1rem 1.1rem;font-weight:800}
.ap-lec summary::-webkit-details-marker{display:none}
.ap-lec .n{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#f1f5f2;font-weight:900;flex:none}
.ap-lec.hecha .n{background:#16b34a;color:#fff}
.ap-lec summary small{margin-left:auto;color:#6b7570;font-weight:700;white-space:nowrap}
.ap-lec .cuerpo{padding:0 1.2rem 1.1rem 3.9rem}
@media (max-width:560px){.ap-lec .cuerpo{padding:0 1rem 1rem}}
.ap-lec ul{padding-left:1.1rem;margin:0 0 .8rem}.ap-lec li{margin-bottom:.45rem;line-height:1.5}
.ap-tip{background:#fffbeb;border:1px solid #fde68a;color:#78501a;border-radius:14px;padding:.6rem .85rem;font-size:.9rem;font-weight:600;margin-bottom:.8rem}
.ap-prueba{background:#fff;border:1.5px solid #bfeccb;border-radius:24px;padding:1.2rem 1.3rem}
.ap-op{display:block;border:1.5px solid #e3ebe6;border-radius:14px;padding:.6rem .85rem;margin-bottom:.45rem;cursor:pointer;font-weight:600}
.ap-op:has(input:checked){border-color:#16b34a;background:#f2fbf3}
.ap-op input{margin-right:.5rem;accent-color:#16b34a}
</style>
