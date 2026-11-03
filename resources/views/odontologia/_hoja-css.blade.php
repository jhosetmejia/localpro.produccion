{{-- Estilo de las hojas imprimibles del consultorio odontológico (presupuesto, consentimiento) --}}
<style>
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;color:#0f172a;margin:0;background:#e6f4f6;font-size:13px;line-height:1.5}
.hoja{background:#fff;max-width:820px;margin:20px auto;padding:0 0 30px;box-shadow:0 10px 30px -18px rgba(0,0,0,.4);overflow:hidden;border-radius:6px}
.cab{display:flex;gap:16px;align-items:center;padding:22px 38px;background:linear-gradient(120deg,#ecfeff,#f0fdfa 60%,#fff);border-bottom:3px solid #0891b2;position:relative}
.cab::after{content:"";position:absolute;right:26px;top:10px;width:80px;height:80px;opacity:.12;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cpath fill='%230891b2' d='M20 4c-8 0-14 6-14 15 0 7 3 11 5 17 2 7 2 24 8 24 5 0 4-17 9-17h8c5 0 4 17 9 17 6 0 6-17 8-24 2-6 5-10 5-17C58 10 52 4 44 4c-5 0-8 3-12 3S25 4 20 4z'/%3E%3C/svg%3E") no-repeat center/contain}
.cab img{max-height:62px;max-width:140px;object-fit:contain}
.cab .neg b{font-size:17px;display:block;color:#0e7490}.cab .neg span{color:#475569;font-size:12px;display:block}
.cab .tit{margin-left:auto;text-align:right;position:relative;z-index:1}.cab .tit b{font-size:15px;display:block;text-transform:uppercase;letter-spacing:.04em}.cab .tit span{font-size:12px;color:#475569}
.cuerpo{padding:18px 38px 0}
.pac{display:grid;grid-template-columns:repeat(4,1fr);gap:6px 14px;border:1px solid #cdeff5;background:#f8feff;border-radius:10px;padding:10px 12px;margin-bottom:14px}
.pac small{display:block;color:#64748b;font-size:10.5px;text-transform:uppercase}.pac b{font-size:12.5px}.pac .ancho{grid-column:span 2}
h3{font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:#0e7490;border-bottom:1px solid #cdeff5;padding-bottom:3px;margin:16px 0 6px}
table{width:100%;border-collapse:collapse}
th{font-size:10.5px;text-transform:uppercase;color:#64748b;text-align:left;border-bottom:1.5px solid #0891b2;padding:6px}
td{padding:7px 6px;border-bottom:1px solid #e2eef1}
.r{text-align:right}.pz{display:inline-block;background:#ecfeff;color:#0e7490;border-radius:6px;padding:1px 6px;font-weight:bold}
.tot{margin-left:auto;width:300px;margin-top:10px}.tot div{display:flex;justify-content:space-between;padding:3px 0}.tot .g{font-size:16px;font-weight:bold;border-top:1.5px solid #0891b2;padding-top:6px;margin-top:3px}
.txt{white-space:pre-line;font-size:13.5px;line-height:1.65;text-align:justify}
.firmas{display:flex;justify-content:space-between;gap:30px;margin-top:44px}
.firmas div{flex:1;text-align:center}.firmas img{max-height:80px;max-width:240px;display:block;margin:0 auto -4px}
.firmas .linea{border-top:1px solid #0f172a;padding-top:4px}.firmas b{display:block}.firmas span{display:block;font-size:11.5px;color:#334155}
.pie{margin:24px 38px 0;border-top:1px solid #e2eef1;padding-top:8px;font-size:10.5px;color:#64748b;display:flex;justify-content:space-between;gap:10px}
.bar{max-width:820px;margin:16px auto 0;display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}
.bar button,.bar a{border:0;border-radius:999px;padding:9px 16px;font-weight:bold;cursor:pointer;background:#0e7490;color:#fff;text-decoration:none;font-size:13px}
.bar a.blanco{background:#fff;color:#0f172a;border:1px solid #cbd5e1}.bar a.wa{background:#16a34a}
@media print{body{background:#fff}.hoja{box-shadow:none;margin:0;max-width:none;border-radius:0}.bar{display:none}@page{margin:10mm}.cab{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
@media (max-width:640px){.cab,.cuerpo{padding-left:16px;padding-right:16px}.pac{grid-template-columns:repeat(2,1fr)}.cab{flex-wrap:wrap}.cab .tit{margin-left:0;text-align:left}.firmas{flex-direction:column}.tot{width:100%}}
</style>
