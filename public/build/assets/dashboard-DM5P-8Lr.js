import{i as E}from"./datepicker-C_SEo8OD.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const p=document.querySelector("[data-dashboard-summary-section]");if(p){const g=p.getAttribute("data-dashboard-summary-url");if(g){const a=(r,o,s=700)=>{r.querySelector(".animate-pulse")&&(r.innerText="0");const n=parseInt(r.innerText.replace(/,/g,""))||0,t=parseInt(o)||0;if(n===t){r.innerText=t.toLocaleString();return}const e=t-n,d=performance.now(),l=i=>{const u=i-d,y=Math.min(u/s,1),x=y*(2-y),L=Math.floor(n+e*x);r.innerText=L.toLocaleString(),y<1?requestAnimationFrame(l):r.innerText=t.toLocaleString()};requestAnimationFrame(l)};(async()=>{try{const r=await fetch(g,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!r.ok)throw new Error("Dashboard summary fetch failed");const o=await r.json();o.success&&o.data&&Object.entries(o.data).forEach(([s,f])=>{p.querySelectorAll(`[data-dashboard-count="${s}"]`).forEach(t=>{a(t,f)})})}catch(r){console.warn("Dashboard Summary Error:",r),p.querySelectorAll("[data-dashboard-count]").forEach(s=>{s.querySelector(".animate-pulse")&&(s.innerText="0")})}})()}}const k=document.querySelector("[data-worked-time-section]");if(k){const g=k.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const a=document.getElementById("worked-time-datepicker"),c=(n=0)=>{const t=new Date;t.setDate(t.getDate()+n);const e=t.getFullYear(),d=String(t.getMonth()+1).padStart(2,"0"),l=String(t.getDate()).padStart(2,"0");return`${e}-${d}-${l}`},r=n=>n?n.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",o=async n=>{const t=document.getElementById("worked-time-table-body");if(t){t.innerHTML=`
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const e=k.getAttribute("data-worked-time-url"),d=await fetch(`${e}?date=${n}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!d.ok)throw new Error("Worked time fetch failed");const l=await d.json();l.success&&Array.isArray(l.data)&&(l.data.length===0?t.innerHTML=`
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:t.innerHTML=l.data.map(i=>{const u=i.end_time==="Running"?'<span class="text-success-300 font-semibold">Running</span>':r(i.end_time),y=i.shift_working_hour==="Day Off"?'<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>':r(i.shift_working_hour);return`
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                         <div class="flex items-center gap-2">
                                             ${i.user_avatar_html||""}
                                             <span>${r(i.user_name)}</span>
                                         </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${r(i.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(i.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${u}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(i.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${y}</td>
                                </tr>
                            `}).join(""))}catch(e){console.error("Worked Time Load Error:",e),t.innerHTML=`
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},s=n=>{g.forEach(t=>{const d=t.getAttribute("data-worked-time-filter")===n;t.setAttribute("aria-pressed",d?"true":"false"),t.classList.toggle("active",d)})},f=n=>{const t=document.getElementById("view-all-daily-time");if(t){const e=t.getAttribute("data-base-url");e&&(t.href=`${e}?from_date=${n}&to_date=${n}`)}};a&&E("#worked-time-datepicker",{onChange:(n,t)=>{if(t){const e=c(0),d=c(-1);s(t===e?"today":t===d?"yesterday":null),o(t),f(t)}}}),g.forEach(n=>{n.addEventListener("click",()=>{const t=n.getAttribute("data-worked-time-filter");if(t==="today"){const e=c(0);a&&(a.value=e,a._flatpickr&&a._flatpickr.setDate(e)),s("today"),o(e),f(e)}else if(t==="yesterday"){const e=c(-1);a&&(a.value=e,a._flatpickr&&a._flatpickr.setDate(e)),s("yesterday"),o(e),f(e)}})})}const m=document.querySelector("[data-running-tasks-card]");if(m){const g=m.querySelector("[data-running-tasks-table-body]"),a=m.querySelector("[data-running-tasks-load-more-btn]"),c=m.querySelector("[data-running-tasks-load-more-container]"),r=m.querySelector("[data-running-tasks-empty-row]");let o=!1;const s=t=>t?t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",f=(t,e,d="...")=>t?t.length>e?t.substring(0,e)+d:t:"",n=async()=>{if(o||!(m.getAttribute("data-running-tasks-has-more")==="true"))return;const e=m.getAttribute("data-running-tasks-url"),d=m.getAttribute("data-running-tasks-next-page");if(!(!e||!d)){o=!0,a&&(a.disabled=!0,a.textContent="Loading More...");try{const l=await fetch(`${e}?page=${d}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!l.ok)throw new Error("Failed to load running tasks");const i=await l.json();i.success&&i.data&&(i.data.length>0&&r&&r.classList.add("hidden"),i.data.forEach(u=>{const y=document.createElement("tr");y.className="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150";const x=`/tasks/${u.task_id}/edit`;y.innerHTML=`
                            <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                <div class="flex items-center gap-2">
                                    ${u.user_avatar_html||""}
                                    <span>${s(u.user_name)}</span>
                                </div>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                                <a href="${x}">
                                    ${s(f(u.task_name,30))}
                                </a>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${s(u.estimated_time)}</td>
                            <td class="py-3.5 text-sm ${s(u.color_class)}">${s(u.worked_time)}</td>
                        `,g.appendChild(y)}),m.setAttribute("data-running-tasks-has-more",i.has_more_pages?"true":"false"),m.setAttribute("data-running-tasks-next-page",i.next_page||""),i.has_more_pages||c&&c.classList.add("hidden"))}catch(l){console.error("Running Tasks Load Error:",l)}finally{o=!1,a&&(a.disabled=!1,a.textContent="Load More")}}};a&&a.addEventListener("click",n)}const v=document.querySelectorAll("[data-dashboard-tile]"),h=document.getElementById("dashboard-tile-modal"),b=document.getElementById("dashboard-tile-modal-content"),w=document.querySelector("[data-dashboard-summary-section]");if(v.length>0&&h&&b&&w){const g=w.getAttribute("data-dashboard-tile-url");v.forEach(c=>{c.addEventListener("click",async()=>{const r=c.getAttribute("data-dashboard-tile");h.classList.remove("hidden"),h.classList.add("flex"),b.innerHTML=`
                    <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                        <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Loading...</h3>
                        <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="flex-1 p-6 flex justify-center items-center">
                        <span class="animate-pulse text-bgray-500 font-semibold text-lg">Fetching records...</span>
                    </div>
                `;try{const o=await fetch(`${g}?type=${r}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!o.ok)throw new Error("Failed to load tile details");const s=await o.json();s.success&&s.html&&(b.innerHTML=s.html)}catch(o){console.error("Tile Details Load Error:",o),b.innerHTML=`
                        <div class="flex items-center justify-between border-b border-bgray-200 px-6 py-4 dark:border-darkblack-400">
                            <h3 class="text-xl font-bold text-bgray-900 dark:text-white">Error</h3>
                            <button type="button" data-dashboard-tile-close class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-bgray-500 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white transition-colors duration-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="flex-1 p-6 flex justify-center items-center">
                            <span class="text-red-500 font-semibold">Failed to load details. Please try again.</span>
                        </div>
                    `}})});const a=document.querySelector("[data-dashboard-tile-overlay]");a&&a.addEventListener("click",()=>{h.classList.add("hidden"),h.classList.remove("flex")}),document.addEventListener("click",c=>{c.target.closest("[data-dashboard-tile-close]")&&(h.classList.add("hidden"),h.classList.remove("flex"))})}});
