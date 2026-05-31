import{i as b}from"./datepicker-CA2wRfma.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const u=document.querySelector("[data-dashboard-summary-section]");if(u){const l=u.getAttribute("data-dashboard-summary-url");if(l){const s=(r,i,o=700)=>{r.querySelector(".animate-pulse")&&(r.innerText="0");const t=parseInt(r.innerText.replace(/,/g,""))||0,e=parseInt(i)||0;if(t===e){r.innerText=e.toLocaleString();return}const n=e-t,c=performance.now(),d=f=>{const g=f-c,p=Math.min(g/o,1),k=p*(2-p),h=Math.floor(t+n*k);r.innerText=h.toLocaleString(),p<1?requestAnimationFrame(d):r.innerText=e.toLocaleString()};requestAnimationFrame(d)};(async()=>{try{const r=await fetch(l,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!r.ok)throw new Error("Dashboard summary fetch failed");const i=await r.json();i.success&&i.data&&Object.entries(i.data).forEach(([o,a])=>{u.querySelectorAll(`[data-dashboard-count="${o}"]`).forEach(e=>{s(e,a)})})}catch(r){console.warn("Dashboard Summary Error:",r),u.querySelectorAll("[data-dashboard-count]").forEach(o=>{o.querySelector(".animate-pulse")&&(o.innerText="0")})}})()}}const y=document.querySelector("[data-worked-time-section]");if(y){const l=y.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const s=document.getElementById("worked-time-datepicker"),m=(a=0)=>{const t=new Date;t.setDate(t.getDate()+a);const e=t.getFullYear(),n=String(t.getMonth()+1).padStart(2,"0"),c=String(t.getDate()).padStart(2,"0");return`${e}-${n}-${c}`},r=a=>a?a.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",i=async a=>{const t=document.getElementById("worked-time-table-body");if(t){t.innerHTML=`
                <tr>
                    <td colspan="4" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const e=y.getAttribute("data-worked-time-url"),n=await fetch(`${e}?date=${a}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!n.ok)throw new Error("Worked time fetch failed");const c=await n.json();c.success&&Array.isArray(c.data)&&(c.data.length===0?t.innerHTML=`
                            <tr>
                                <td colspan="4" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:t.innerHTML=c.data.map(d=>`
                            <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${r(d.user_name)}</td>
                                <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${r(d.date)}</td>
                                <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(d.shift_working_hour)}</td>
                                <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(d.total_worked_time)}</td>
                            </tr>
                        `).join(""))}catch(e){console.error("Worked Time Load Error:",e),t.innerHTML=`
                    <tr>
                        <td colspan="4" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},o=a=>{l.forEach(t=>{const n=t.getAttribute("data-worked-time-filter")===a;t.setAttribute("aria-pressed",n?"true":"false"),t.classList.toggle("active",n)})};s&&b("#worked-time-datepicker",{onChange:(a,t)=>{if(t){const e=m(0),n=m(-1);o(t===e?"today":t===n?"yesterday":null),i(t)}}}),l.forEach(a=>{a.addEventListener("click",()=>{const t=a.getAttribute("data-worked-time-filter");if(t==="today"){const e=m(0);s&&(s.value=e,s._flatpickr&&s._flatpickr.setDate(e)),o("today"),i(e)}else if(t==="yesterday"){const e=m(-1);s&&(s.value=e,s._flatpickr&&s._flatpickr.setDate(e)),o("yesterday"),i(e)}})})}});
