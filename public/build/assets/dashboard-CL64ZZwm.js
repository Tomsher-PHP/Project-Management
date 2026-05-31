import{i as x}from"./datepicker-CA2wRfma.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const u=document.querySelector("[data-dashboard-summary-section]");if(u){const l=u.getAttribute("data-dashboard-summary-url");if(l){const n=(a,c,s=700)=>{a.querySelector(".animate-pulse")&&(a.innerText="0");const t=parseInt(a.innerText.replace(/,/g,""))||0,e=parseInt(c)||0;if(t===e){a.innerText=e.toLocaleString();return}const i=e-t,d=performance.now(),o=y=>{const p=y-d,g=Math.min(p/s,1),k=g*(2-g),h=Math.floor(t+i*k);a.innerText=h.toLocaleString(),g<1?requestAnimationFrame(o):a.innerText=e.toLocaleString()};requestAnimationFrame(o)};(async()=>{try{const a=await fetch(l,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!a.ok)throw new Error("Dashboard summary fetch failed");const c=await a.json();c.success&&c.data&&Object.entries(c.data).forEach(([s,r])=>{u.querySelectorAll(`[data-dashboard-count="${s}"]`).forEach(e=>{n(e,r)})})}catch(a){console.warn("Dashboard Summary Error:",a),u.querySelectorAll("[data-dashboard-count]").forEach(s=>{s.querySelector(".animate-pulse")&&(s.innerText="0")})}})()}}const f=document.querySelector("[data-worked-time-section]");if(f){const l=f.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const n=document.getElementById("worked-time-datepicker"),m=(r=0)=>{const t=new Date;t.setDate(t.getDate()+r);const e=t.getFullYear(),i=String(t.getMonth()+1).padStart(2,"0"),d=String(t.getDate()).padStart(2,"0");return`${e}-${i}-${d}`},a=r=>r?r.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",c=async r=>{const t=document.getElementById("worked-time-table-body");if(t){t.innerHTML=`
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const e=f.getAttribute("data-worked-time-url"),i=await fetch(`${e}?date=${r}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!i.ok)throw new Error("Worked time fetch failed");const d=await i.json();d.success&&Array.isArray(d.data)&&(d.data.length===0?t.innerHTML=`
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:t.innerHTML=d.data.map(o=>{const y=o.end_time==="Running"?'<span class="text-success-300 font-semibold">Running</span>':a(o.end_time),p=o.shift_working_hour==="Day Off"?'<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>':a(o.shift_working_hour);return`
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                         <div class="flex items-center gap-2">
                                             ${o.user_avatar_html||""}
                                             <span>${a(o.user_name)}</span>
                                         </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${a(o.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(o.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${y}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(o.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${p}</td>
                                </tr>
                            `}).join(""))}catch(e){console.error("Worked Time Load Error:",e),t.innerHTML=`
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},s=r=>{l.forEach(t=>{const i=t.getAttribute("data-worked-time-filter")===r;t.setAttribute("aria-pressed",i?"true":"false"),t.classList.toggle("active",i)})};n&&x("#worked-time-datepicker",{onChange:(r,t)=>{if(t){const e=m(0),i=m(-1);s(t===e?"today":t===i?"yesterday":null),c(t)}}}),l.forEach(r=>{r.addEventListener("click",()=>{const t=r.getAttribute("data-worked-time-filter");if(t==="today"){const e=m(0);n&&(n.value=e,n._flatpickr&&n._flatpickr.setDate(e)),s("today"),c(e)}else if(t==="yesterday"){const e=m(-1);n&&(n.value=e,n._flatpickr&&n._flatpickr.setDate(e)),s("yesterday"),c(e)}})})}});
