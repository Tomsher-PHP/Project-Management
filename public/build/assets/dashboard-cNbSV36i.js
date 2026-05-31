import{i as b}from"./datepicker-CA2wRfma.js";import"./flatpickr.min-Cc3t1Oke.js";document.addEventListener("DOMContentLoaded",()=>{const y=document.querySelector("[data-dashboard-summary-section]");if(y){const p=y.getAttribute("data-dashboard-summary-url");if(p){const i=(r,c,n=700)=>{r.querySelector(".animate-pulse")&&(r.innerText="0");const t=parseInt(r.innerText.replace(/,/g,""))||0,e=parseInt(c)||0;if(t===e){r.innerText=e.toLocaleString();return}const o=e-t,d=performance.now(),s=u=>{const m=u-d,f=Math.min(m/n,1),k=f*(2-f),x=Math.floor(t+o*k);r.innerText=x.toLocaleString(),f<1?requestAnimationFrame(s):r.innerText=e.toLocaleString()};requestAnimationFrame(s)};(async()=>{try{const r=await fetch(p,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!r.ok)throw new Error("Dashboard summary fetch failed");const c=await r.json();c.success&&c.data&&Object.entries(c.data).forEach(([n,a])=>{y.querySelectorAll(`[data-dashboard-count="${n}"]`).forEach(e=>{i(e,a)})})}catch(r){console.warn("Dashboard Summary Error:",r),y.querySelectorAll("[data-dashboard-count]").forEach(n=>{n.querySelector(".animate-pulse")&&(n.innerText="0")})}})()}}const h=document.querySelector("[data-worked-time-section]");if(h){const p=h.querySelectorAll("[data-worked-time-filter]");document.getElementById("custom-datepicker-container");const i=document.getElementById("worked-time-datepicker"),g=(a=0)=>{const t=new Date;t.setDate(t.getDate()+a);const e=t.getFullYear(),o=String(t.getMonth()+1).padStart(2,"0"),d=String(t.getDate()).padStart(2,"0");return`${e}-${o}-${d}`},r=a=>a?a.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",c=async a=>{const t=document.getElementById("worked-time-table-body");if(t){t.innerHTML=`
                <tr>
                    <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">
                        <span class="inline-block animate-pulse">Loading worked time...</span>
                    </td>
                </tr>
            `;try{const e=h.getAttribute("data-worked-time-url"),o=await fetch(`${e}?date=${a}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!o.ok)throw new Error("Worked time fetch failed");const d=await o.json();d.success&&Array.isArray(d.data)&&(d.data.length===0?t.innerHTML=`
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-bgray-500 dark:text-bgray-400">No worked time logged for this date.</td>
                            </tr>
                        `:t.innerHTML=d.data.map(s=>{const u=s.end_time==="Running"?'<span class="text-success-300 font-semibold">Running</span>':r(s.end_time),m=s.shift_working_hour==="Day Off"?'<span class="inline-flex items-center text-xs font-bold text-amber-700 dark:text-amber-400">Day Off</span>':r(s.shift_working_hour);return`
                                <tr class="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150">
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                         <div class="flex items-center gap-2">
                                             ${s.user_avatar_html||""}
                                             <span>${r(s.user_name)}</span>
                                         </div>
                                    </td>
                                    <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">${r(s.date)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(s.start_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${u}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${r(s.total_worked_time)}</td>
                                    <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${m}</td>
                                </tr>
                            `}).join(""))}catch(e){console.error("Worked Time Load Error:",e),t.innerHTML=`
                    <tr>
                        <td colspan="6" class="py-8 text-center text-sm text-red-500">Failed to load worked time.</td>
                    </tr>
                `}}},n=a=>{p.forEach(t=>{const o=t.getAttribute("data-worked-time-filter")===a;t.setAttribute("aria-pressed",o?"true":"false"),t.classList.toggle("active",o)})};i&&b("#worked-time-datepicker",{onChange:(a,t)=>{if(t){const e=g(0),o=g(-1);n(t===e?"today":t===o?"yesterday":null),c(t)}}}),p.forEach(a=>{a.addEventListener("click",()=>{const t=a.getAttribute("data-worked-time-filter");if(t==="today"){const e=g(0);i&&(i.value=e,i._flatpickr&&i._flatpickr.setDate(e)),n("today"),c(e)}else if(t==="yesterday"){const e=g(-1);i&&(i.value=e,i._flatpickr&&i._flatpickr.setDate(e)),n("yesterday"),c(e)}})})}const l=document.querySelector("[data-running-tasks-card]");if(l){const p=l.querySelector("[data-running-tasks-scroll-container]"),i=l.querySelector("[data-running-tasks-table-body]"),g=l.querySelector("[data-running-tasks-loading-indicator]"),r=l.querySelector("[data-running-tasks-no-more]"),c=l.querySelector("[data-running-tasks-empty-row]");let n=!1;const a=e=>e?e.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"):"",t=async()=>{if(n||!(l.getAttribute("data-running-tasks-has-more")==="true"))return;const o=l.getAttribute("data-running-tasks-url"),d=l.getAttribute("data-running-tasks-next-page");if(!(!o||!d)){n=!0,g&&g.classList.remove("hidden");try{const s=await fetch(`${o}?page=${d}`,{headers:{"X-Requested-With":"XMLHttpRequest",Accept:"application/json"}});if(!s.ok)throw new Error("Failed to load running tasks");const u=await s.json();u.success&&u.data&&(u.data.length>0&&c&&c.classList.add("hidden"),u.data.forEach(m=>{const f=document.createElement("tr");f.className="hover:bg-bgray-50/50 dark:hover:bg-darkblack-500/20 transition duration-150";const k=`/tasks/${m.task_id}/edit`;f.innerHTML=`
                            <td class="py-3.5 text-sm text-bgray-900 dark:text-white font-semibold">
                                <div class="flex items-center gap-2">
                                    ${m.user_avatar_html||""}
                                    <span>${a(m.user_name)}</span>
                                </div>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-success-300 hover:text-success-400 transition-colors">
                                <a href="${k}">
                                    ${a(m.task_name)}
                                </a>
                            </td>
                            <td class="py-3.5 text-sm font-semibold text-bgray-900 dark:text-white">${a(m.estimated_time)}</td>
                            <td class="py-3.5 text-sm ${a(m.color_class)}">${a(m.worked_time)}</td>
                        `,i.appendChild(f)}),l.setAttribute("data-running-tasks-has-more",u.has_more_pages?"true":"false"),l.setAttribute("data-running-tasks-next-page",u.next_page||""),u.has_more_pages||r&&r.classList.remove("hidden"))}catch(s){console.error("Running Tasks Load Error:",s)}finally{n=!1,g&&g.classList.add("hidden")}}};p&&p.addEventListener("scroll",()=>{const{scrollTop:e,scrollHeight:o,clientHeight:d}=p;o-e-d<20&&t()})}});
