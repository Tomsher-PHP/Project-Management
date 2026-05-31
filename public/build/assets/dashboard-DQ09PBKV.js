import{i as x}from"./datepicker-CA2wRfma.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const u=document.querySelector("[data-dashboard-summary-section]");if(u){const l=u.getAttribute("data-dashboard-summary-url");if(l){const o=(a,c,s=700)=>{a.querySelector(".animate-pulse")&&(a.innerText="0");const t=parseInt(a.innerText.replace(/,/g,""))||0,e=parseInt(c)||0;if(t===e){a.innerText=e.toLocaleString();return}const n=e-t,d=performance.now(),i=y=>{const p=y-d,g=Math.min(p/s,1),k=g*(2-g),h=Math.floor(t+n*k);a.innerText=h.toLocaleString(),g<1?requestAnimationFrame(i):a.innerText=e.toLocaleString()};requestAnimationFrame(i)};(async()=>{try{const a=await fetch(l,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!a.ok)throw new Error("Dashboard summary fetch failed");const c=await a.json();c.success&&c.data&&Object.entries(c.data).forEach(([s,r])=>{u.querySelectorAll(`[data-dashboard-count="${s}"]`).forEach(e=>{o(e,r)})})}catch(a){console.warn("Dashboard Summary Error:",a),u.querySelectorAll("[data-dashboard-count]").forEach(s=>{s.querySelector(".animate-pulse")&&(s.innerText="0")})}})()}}const f=document.querySelector("[data-worked-time-section]");if(f){const l=f.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const o=document.getElementById("worked-time-datepicker"),m=(r=0)=>{const t=new Date;t.setDate(t.getDate()+r);const e=t.getFullYear(),n=String(t.getMonth()+1).padStart(2,"0"),d=String(t.getDate()).padStart(2,"0");return`${e}-${n}-${d}`},a=r=>r?r.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",c=async r=>{const t=document.getElementById("worked-time-table-body");if(t){t.innerHTML=`
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const e=f.getAttribute("data-worked-time-url"),n=await fetch(`${e}?date=${r}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!n.ok)throw new Error("Worked time fetch failed");const d=await n.json();d.success&&Array.isArray(d.data)&&(d.data.length===0?t.innerHTML=`
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:t.innerHTML=d.data.map(i=>{const y=i.end_time==="Running"?'<span class="text-success-300 font-semibold">Running</span>':a(i.end_time),p=i.shift_working_hour==="Day Off"?'<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>':a(i.shift_working_hour);return`
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${a(i.user_name)}</td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${a(i.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(i.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${y}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(i.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${p}</td>
                                </tr>
                            `}).join(""))}catch(e){console.error("Worked Time Load Error:",e),t.innerHTML=`
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},s=r=>{l.forEach(t=>{const n=t.getAttribute("data-worked-time-filter")===r;t.setAttribute("aria-pressed",n?"true":"false"),t.classList.toggle("active",n)})};o&&x("#worked-time-datepicker",{onChange:(r,t)=>{if(t){const e=m(0),n=m(-1);s(t===e?"today":t===n?"yesterday":null),c(t)}}}),l.forEach(r=>{r.addEventListener("click",()=>{const t=r.getAttribute("data-worked-time-filter");if(t==="today"){const e=m(0);o&&(o.value=e,o._flatpickr&&o._flatpickr.setDate(e)),s("today"),c(e)}else if(t==="yesterday"){const e=m(-1);o&&(o.value=e,o._flatpickr&&o._flatpickr.setDate(e)),s("yesterday"),c(e)}})})}});
