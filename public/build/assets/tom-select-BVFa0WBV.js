function k(y=document){const v=(e="")=>String(e).trim().toLowerCase(),g=e=>{const l="ts-selection-count",a="ts-selection-item-hidden";let s=null;e.wrapper.classList.add("ts-wrapper-multiple-compact");const o=()=>{e.control.querySelector(`.${l}`)?.setAttribute("aria-expanded","false"),s&&(s.remove(),s=null,document.removeEventListener("mousedown",t),document.removeEventListener("keydown",r),window.removeEventListener("resize",o))},t=c=>{s?.contains(c.target)||c.target instanceof Element&&c.target.closest(`.${l}`)||o()},r=c=>{c.key==="Escape"&&(o(),e.focus())},n=c=>{s&&(!(c.target instanceof Element)||!c.target.closest(`.${l}`))&&o()},u=()=>{if(!s)return;const c=s.querySelector(".ts-selected-items-list"),b=c.scrollTop,i=new Map(Array.from(c.children).map(m=>[m.dataset.value,m]));e.items.forEach(m=>{const d=String(m);let f=i.get(d);if(!f){f=document.createElement("div"),f.className="ts-selected-items-option",f.dataset.value=d;const T=document.createElement("span");T.className="ts-selected-items-label";const w=document.createElement("button");w.type="button",w.className="ts-selected-items-remove",w.textContent="×",w.addEventListener("click",L=>{const $=e.getItem(d);e.isLocked||!$||!e.shouldDelete([$],L)||(e.removeItem($),e.refreshOptions(!1),e.inputState())}),f.append(T,w)}const x=f.querySelector(".ts-selected-items-label"),I=f.querySelector(".ts-selected-items-remove");x.textContent=String(e.options[d]?.text??d),I.setAttribute("aria-label",`Remove ${x.textContent}`),c.append(f),i.delete(d)}),i.forEach(m=>m.remove()),c.scrollTop=b},h=c=>{const b=e.ignoreFocus;try{e.ignoreFocus=!0,e.close()}finally{e.ignoreFocus=b}o(),s=document.createElement("div"),s.className="ts-selected-items-popover",s.setAttribute("role","dialog"),s.setAttribute("aria-label","Selected items"),s.innerHTML=`
                <div class="ts-selected-items-heading">Selected items</div>
                <div class="ts-selected-items-list"></div>
            `,document.body.append(s),s.addEventListener("mousedown",x=>{x.stopPropagation()}),s.addEventListener("click",x=>{x.stopPropagation()});const i=e.wrapper.getBoundingClientRect(),m=8,d=Math.min(i.width,window.innerWidth-m*2),f=Math.min(Math.max(m,i.left),window.innerWidth-d-m);s.style.width=`${d}px`,s.style.left=`${f+window.scrollX}px`,s.style.top=`${i.bottom+window.scrollY+4}px`,u(),document.addEventListener("mousedown",t),document.addEventListener("keydown",r),window.addEventListener("resize",o),s.querySelector(".ts-selected-items-remove")?.focus(),c.setAttribute("aria-expanded","true")},p=()=>{const c=Array.from(e.control.querySelectorAll(".item")),b=Math.max(0,c.length-1);c.forEach((m,d)=>{m.classList.toggle(a,d>0)});let i=e.control.querySelector(`.${l}`);if(b===0){i?.remove(),u();return}if(!i){i=document.createElement("button"),i.type="button",i.className=l,i.setAttribute("aria-haspopup","dialog"),i.setAttribute("aria-expanded","false"),i.addEventListener("mousedown",d=>{d.preventDefault()}),i.addEventListener("click",d=>{d.preventDefault(),d.stopPropagation(),e.isDisabled||h(i)});const m=e.control.querySelector("input");e.control.insertBefore(i,m)}i.textContent=`+${b}`,i.setAttribute("aria-label",`Show all ${c.length} selected items`),i.disabled=e.isDisabled,u()};e.on("item_add",p),e.on("item_remove",p),e.on("change",p),e.control.addEventListener("mousedown",n),e.on("destroy",()=>{e.control.removeEventListener("mousedown",n),o()}),p()},S=(e,l)=>{!e?.wrapper||!e?.control||!l.disabled||(e.wrapper.classList.add("opacity-100"),e.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-300"),e.control.classList.remove("bg-white"),e.control.querySelectorAll(".item, input, .ts-control > div").forEach(a=>{a.classList.add("text-bgray-600","dark:text-bgray-300")}))},A=(e,l)=>{if(!l||l.tagName!=="SELECT"||!l.multiple)return;const a=Array.from(l.options).filter(o=>o.selected&&String(o.value??"")!=="");if(!a.length)return;const s=a.map(o=>String(o.value));a.forEach(o=>{const t=String(o.value);if(!Object.prototype.hasOwnProperty.call(e.options,t)){let r=o.dataset.subtype||"",n=o.dataset.email||"";if(o.dataset.data)try{const u=JSON.parse(o.dataset.data);!r&&u?.subtype&&(r=u.subtype),!r&&u?.email&&(r=u.email),!n&&u?.email&&(n=u.email)}catch{}e.addOption({value:t,text:o.textContent.trim(),subtype:r||n,email:n||r})}}),e.setValue(s,!0)},E=(e,l)=>{if(!l||l.tagName!=="SELECT")return;let a=!1;if(Array.from(l.options).forEach(s=>{const o=String(s.value??"");if(!Object.prototype.hasOwnProperty.call(e.options,o))return;let t=s.dataset.subtype||"",r=s.dataset.email||"";if(s.dataset.data)try{const n=JSON.parse(s.dataset.data);!t&&n?.subtype&&(t=n.subtype),!t&&n?.email&&(t=n.email),!r&&n?.email&&(r=n.email)}catch{}(t||r)&&(a=!0,e.options[o]={...e.options[o],subtype:t||r,email:r||t})}),a){e.clearCache(),e.refreshOptions(!1);const s=e.getValue();s!=null&&s!==""&&(!Array.isArray(s)||s.length>0)&&e.setValue(s,!0)}};y.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(e=>{if(e.tomselect)return;const l={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["remove_button"],dropdownParent:"body"};e.dataset.renderSubtype==="true"&&(l.render={option:function(s,o){const t=s.subtype||s.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${o(s.text)}
                            </div>

                            ${t?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${o(t)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(s,o){const t=s.subtype||s.email||"";return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">
                                ${o(s.text)}
                            </span>

                            ${t?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${o(t)}
                                        </span>
                                    `:""}
                        </div>
                    `}});const a=new TomSelect(e,l);e.dataset.renderSubtype==="true"&&e.tagName==="SELECT"&&E(a,e),S(a,e)}),y.querySelectorAll("select.tom-select, input.tom-select").forEach(e=>{if(e.tomselect)return;const l=e.dataset.sort!="0",a={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(o,t){const r=o.subtype||o.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${t(o.text)}
                            </div>

                            ${r?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${t(r)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(o,t){const r=o.subtype||o.email||"";return`
                        <div>
                            <span class="font-medium">
                                ${t(o.text)}
                            </span>

                            ${r?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${t(r)}
                                        </span>
                                    `:""}
                        </div>
                    `}}};l&&(a.sortField={field:"text",direction:"asc"});const s=new TomSelect(e,a);E(s,e),e.multiple&&(A(s,e),g(s)),S(s,e)}),y.querySelectorAll("select.tom-select-tags, input.tom-select-tags, select.tom-select-add").forEach(e=>{if(e.tomselect)return;const l=e.dataset.placeholder||"Search or add tags",a=e.dataset.maxItems||null,s=new TomSelect(e,{plugins:["remove_button"],maxItems:a,persist:!1,dropdownParent:"body",createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:l,create:e.disabled?!1:o=>{const t=String(o||"").trim();return{value:t,text:t}},createFilter(o){const t=v(o);return t?!Object.values(this.options).some(r=>v(r?.text??r?.value??"")===t):!1},score(o){const t=v(o);return function(r){const n=v(r.text);return t?n===t?2:n.includes(t)?1:0:1}},render:{option(o,t){return`
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium">
                                    ${t(o.text)}
                                </span>

                                ${o.$option?"":`
                                            <span class="text-xs font-semibold text-success-400">
                                                Create
                                            </span>
                                        `}
                            </div>
                        `},item(o,t){return`
                            <div class="font-medium">
                                ${t(o.text)}
                            </div>
                        `}}});S(s,e)}),y.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(e=>{if(e.tomselect)return;const l=()=>!e||e.tagName!=="SELECT"?[]:Array.from(e.options).filter(t=>t.selected&&String(t.value??"")!=="").map(t=>String(t.value)),a=l();console.log("[TomSelect] BEFORE INIT",e.name,a);const s=new TomSelect(e,{plugins:["remove_button","dropdown_input"],maxItems:null,searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(t,r){const n=t.subtype||t.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${r(t.text)}
                            </div>

                            ${n?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${r(n)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(t,r){const n=t.subtype||t.email||"";return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">
                                ${r(t.text)}
                            </span>

                            ${n?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${r(n)}
                                        </span>
                                    `:""}
                        </div>
                    `}}});console.log("[TomSelect] AFTER INIT",e.name,{nativeSelected:l(),tomSelectItems:s.items,tomSelectValue:s.getValue()}),E(s,e);const o=()=>{if(!a.length)return;const t=a.filter(r=>Object.prototype.hasOwnProperty.call(s.options,r));if(console.log("[TomSelect] RESTORE",e.name,{initialSelectedValues:a,validValues:t,options:Object.keys(s.options),before:s.items}),!t.length){console.warn("[TomSelect] Selected values are missing from Tom Select options",{name:e.name,initialSelectedValues:a,options:s.options});return}s.setValue(t,!0),console.log("[TomSelect] RESTORED",e.name,{items:s.items,value:s.getValue()})};o(),setTimeout(()=>{o()},0),requestAnimationFrame(()=>{o()}),g(s),S(s,e)}),y.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(e=>{if(e.tomselect)return;const l=e.dataset.sort!="0",a=e.dataset.route,s={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],sortField:l?{field:"text",direction:"asc"}:null,dropdownParent:"body",render:{option:function(t,r){const n=t.subtype||t.email||t.project_code||"";return`
                        <div>
                            <div class="font-medium">
                                ${r(t.text||t.name)}
                            </div>

                            ${n?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${r(n)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(t,r){const n=t.subtype||t.email||t.project_code||"";return`
                        <div>
                            <span class="font-medium">
                                ${r(t.text||t.name)}
                            </span>

                            ${n?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${r(n)}
                                        </span>
                                    `:""}
                        </div>
                    `}},load:function(t,r){if(!a)return r();const n=a.includes("?")?"&":"?";let u=`${a}${n}q=${encodeURIComponent(t||"")}`;e.dataset.excludeId&&(u+=`&exclude_id=${encodeURIComponent(e.dataset.excludeId)}`),fetch(u).then(h=>h.json()).then(h=>{r(h.map(p=>({value:String(p.value??p.id),text:p.text??p.name,subtype:p.subtype??p.project_code??""})))}).catch(()=>r())}},o=new TomSelect(e,s);E(o,e),S(o,e)}),document.dispatchEvent(new Event("tomselect:ready"))}const C=(y,v)=>{const g=document.getElementById(y);!g||!g.tomselect||(v?g.tomselect.setValue(v):g.tomselect.clear())};export{C as a,k as i};
