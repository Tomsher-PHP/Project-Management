import{i as x}from"./datepicker-C_SEo8OD.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const y=document.querySelector("[data-dashboard-summary-section]");if(y){const f=y.getAttribute("data-dashboard-summary-url");if(f){const r=(a,d,s=700)=>{a.querySelector(".animate-pulse")&&(a.innerText="0");const e=parseInt(a.innerText.replace(/,/g,""))||0,t=parseInt(d)||0;if(e===t){a.innerText=t.toLocaleString();return}const o=t-e,c=performance.now(),i=l=>{const u=l-c,p=Math.min(u/s,1),h=p*(2-p),b=Math.floor(e+o*h);a.innerText=b.toLocaleString(),p<1?requestAnimationFrame(i):a.innerText=t.toLocaleString()};requestAnimationFrame(i)};(async()=>{try{const a=await fetch(f,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!a.ok)throw new Error("Dashboard summary fetch failed");const d=await a.json();d.success&&d.data&&Object.entries(d.data).forEach(([s,n])=>{y.querySelectorAll(`[data-dashboard-count="${s}"]`).forEach(t=>{r(t,n)})})}catch(a){console.warn("Dashboard Summary Error:",a),y.querySelectorAll("[data-dashboard-count]").forEach(s=>{s.querySelector(".animate-pulse")&&(s.innerText="0")})}})()}}const k=document.querySelector("[data-worked-time-section]");if(k){const f=k.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const r=document.getElementById("worked-time-datepicker"),g=(n=0)=>{const e=new Date;e.setDate(e.getDate()+n);const t=e.getFullYear(),o=String(e.getMonth()+1).padStart(2,"0"),c=String(e.getDate()).padStart(2,"0");return`${t}-${o}-${c}`},a=n=>n?n.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",d=async n=>{const e=document.getElementById("worked-time-table-body");if(e){e.innerHTML=`
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const t=k.getAttribute("data-worked-time-url"),o=await fetch(`${t}?date=${n}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!o.ok)throw new Error("Worked time fetch failed");const c=await o.json();c.success&&Array.isArray(c.data)&&(c.data.length===0?e.innerHTML=`
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:e.innerHTML=c.data.map(i=>{const l=i.end_time==="Running"?'<span class="text-success-300 font-semibold">Running</span>':a(i.end_time),u=i.shift_working_hour==="Day Off"?'<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>':a(i.shift_working_hour);return`
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                         <div class="flex items-center gap-2">
                                             ${i.user_avatar_html||""}
                                             <span>${a(i.user_name)}</span>
                                         </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${a(i.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(i.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${l}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(i.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${u}</td>
                                </tr>
                            `}).join(""))}catch(t){console.error("Worked Time Load Error:",t),e.innerHTML=`
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},s=n=>{f.forEach(e=>{const o=e.getAttribute("data-worked-time-filter")===n;e.setAttribute("aria-pressed",o?"true":"false"),e.classList.toggle("active",o)})};r&&x("#worked-time-datepicker",{onChange:(n,e)=>{if(e){const t=g(0),o=g(-1);s(e===t?"today":e===o?"yesterday":null),d(e)}}}),f.forEach(n=>{n.addEventListener("click",()=>{const e=n.getAttribute("data-worked-time-filter");if(e==="today"){const t=g(0);r&&(r.value=t,r._flatpickr&&r._flatpickr.setDate(t)),s("today"),d(t)}else if(e==="yesterday"){const t=g(-1);r&&(r.value=t,r._flatpickr&&r._flatpickr.setDate(t)),s("yesterday"),d(t)}})})}const m=document.querySelector("[data-running-tasks-card]");if(m){const f=m.querySelector("[data-running-tasks-table-body]"),r=m.querySelector("[data-running-tasks-load-more-btn]"),g=m.querySelector("[data-running-tasks-load-more-container]"),a=m.querySelector("[data-running-tasks-empty-row]");let d=!1;const s=t=>t?t.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",n=(t,o,c="...")=>t?t.length>o?t.substring(0,o)+c:t:"",e=async()=>{if(d||!(m.getAttribute("data-running-tasks-has-more")==="true"))return;const o=m.getAttribute("data-running-tasks-url"),c=m.getAttribute("data-running-tasks-next-page");if(!(!o||!c)){d=!0,r&&(r.disabled=!0,r.textContent="Loading More...");try{const i=await fetch(`${o}?page=${c}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!i.ok)throw new Error("Failed to load running tasks");const l=await i.json();l.success&&l.data&&(l.data.length>0&&a&&a.classList.add("hidden"),l.data.forEach(u=>{const p=document.createElement("tr");p.className="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150";const h=`/tasks/${u.task_id}/edit`;p.innerHTML=`
                            <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                <div class="flex items-center gap-2">
                                    ${u.user_avatar_html||""}
                                    <span>${s(u.user_name)}</span>
                                </div>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                                <a href="${h}">
                                    ${s(n(u.task_name,30))}
                                </a>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${s(u.estimated_time)}</td>
                            <td class="py-3.5 text-sm ${s(u.color_class)}">${s(u.worked_time)}</td>
                        `,f.appendChild(p)}),m.setAttribute("data-running-tasks-has-more",l.has_more_pages?"true":"false"),m.setAttribute("data-running-tasks-next-page",l.next_page||""),l.has_more_pages||g&&g.classList.add("hidden"))}catch(i){console.error("Running Tasks Load Error:",i)}finally{d=!1,r&&(r.disabled=!1,r.textContent="Load More")}}};r&&r.addEventListener("click",e)}});
