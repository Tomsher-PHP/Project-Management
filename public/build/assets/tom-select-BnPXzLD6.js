function k(v=document){const g=(e="")=>String(e).trim().toLowerCase(),b=e=>{const l="ts-selection-count",a="ts-selection-item-hidden";let s=null;e.wrapper.classList.add("ts-wrapper-multiple-compact");const o=()=>{e.control.querySelector(`.${l}`)?.setAttribute("aria-expanded","false"),s&&(s.remove(),s=null,document.removeEventListener("mousedown",t),document.removeEventListener("keydown",r),window.removeEventListener("resize",o))},t=c=>{s?.contains(c.target)||c.target instanceof Element&&c.target.closest(`.${l}`)||o()},r=c=>{c.key==="Escape"&&(o(),e.focus())},n=c=>{s&&(!(c.target instanceof Element)||!c.target.closest(`.${l}`))&&o()},m=()=>{if(!s)return;const c=s.querySelector(".ts-selected-items-list"),p=c.scrollTop,i=new Map(Array.from(c.children).map(u=>[u.dataset.value,u]));e.items.forEach(u=>{const d=String(u);let y=i.get(d);if(!y){y=document.createElement("div"),y.className="ts-selected-items-option",y.dataset.value=d;const A=document.createElement("span");A.className="ts-selected-items-label";const w=document.createElement("button");w.type="button",w.className="ts-selected-items-remove",w.textContent="×",w.addEventListener("click",L=>{const $=e.getItem(d);e.isLocked||!$||!e.shouldDelete([$],L)||(e.removeItem($),e.refreshOptions(!1),e.inputState())}),y.append(A,w)}const x=y.querySelector(".ts-selected-items-label"),T=y.querySelector(".ts-selected-items-remove");x.textContent=String(e.options[d]?.text??d),T.setAttribute("aria-label",`Remove ${x.textContent}`),c.append(y),i.delete(d)}),i.forEach(u=>u.remove()),c.scrollTop=p},S=c=>{const p=e.ignoreFocus;try{e.ignoreFocus=!0,e.close()}finally{e.ignoreFocus=p}o(),s=document.createElement("div"),s.className="ts-selected-items-popover",s.setAttribute("role","dialog"),s.setAttribute("aria-label","Selected items"),s.innerHTML=`
                <div class="ts-selected-items-heading">Selected items</div>
                <div class="ts-selected-items-list"></div>
            `,document.body.append(s),s.addEventListener("mousedown",x=>{x.stopPropagation()}),s.addEventListener("click",x=>{x.stopPropagation()});const i=e.wrapper.getBoundingClientRect(),u=8,d=Math.min(i.width,window.innerWidth-u*2),y=Math.min(Math.max(u,i.left),window.innerWidth-d-u);s.style.width=`${d}px`,s.style.left=`${y+window.scrollX}px`,s.style.top=`${i.bottom+window.scrollY+4}px`,m(),document.addEventListener("mousedown",t),document.addEventListener("keydown",r),window.addEventListener("resize",o),s.querySelector(".ts-selected-items-remove")?.focus(),c.setAttribute("aria-expanded","true")},f=()=>{const c=Array.from(e.control.querySelectorAll(".item")),p=Math.max(0,c.length-1);c.forEach((u,d)=>{u.classList.toggle(a,d>0)});let i=e.control.querySelector(`.${l}`);if(p===0){i?.remove(),m();return}if(!i){i=document.createElement("button"),i.type="button",i.className=l,i.setAttribute("aria-haspopup","dialog"),i.setAttribute("aria-expanded","false"),i.addEventListener("mousedown",d=>{d.preventDefault()}),i.addEventListener("click",d=>{d.preventDefault(),d.stopPropagation(),e.isDisabled||S(i)});const u=e.control.querySelector("input");e.control.insertBefore(i,u)}i.textContent=`+${p}`,i.setAttribute("aria-label",`Show all ${c.length} selected items`),i.disabled=e.isDisabled,m()};e.on("item_add",f),e.on("item_remove",f),e.on("change",f),e.control.addEventListener("mousedown",n),e.on("destroy",()=>{e.control.removeEventListener("mousedown",n),o()}),f()},h=(e,l)=>{!e?.wrapper||!e?.control||!l.disabled||(e.wrapper.classList.add("opacity-100"),e.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-300"),e.control.classList.remove("bg-white"),e.control.querySelectorAll(".item, input, .ts-control > div").forEach(a=>{a.classList.add("text-bgray-600","dark:text-bgray-300")}))},I=(e,l)=>{if(!l||l.tagName!=="SELECT"||!l.multiple)return;const a=Array.from(l.options).filter(o=>o.selected&&String(o.value??"")!=="");if(!a.length)return;const s=a.map(o=>String(o.value));a.forEach(o=>{const t=String(o.value);if(!Object.prototype.hasOwnProperty.call(e.options,t)){let r=o.dataset.subtype||"",n=o.dataset.email||"";if(o.dataset.data)try{const m=JSON.parse(o.dataset.data);!r&&m?.subtype&&(r=m.subtype),!r&&m?.email&&(r=m.email),!n&&m?.email&&(n=m.email)}catch{}e.addOption({value:t,text:o.textContent.trim(),subtype:r||n,email:n||r})}}),e.setValue(s,!0)},E=(e,l)=>{if(!l||l.tagName!=="SELECT")return;let a=!1;if(Array.from(l.options).forEach(s=>{const o=String(s.value??"");if(!Object.prototype.hasOwnProperty.call(e.options,o))return;let t=s.dataset.subtype||"",r=s.dataset.email||"";if(s.dataset.data)try{const n=JSON.parse(s.dataset.data);!t&&n?.subtype&&(t=n.subtype),!t&&n?.email&&(t=n.email),!r&&n?.email&&(r=n.email)}catch{}(t||r)&&(a=!0,e.options[o]={...e.options[o],subtype:t||r,email:r||t})}),a){e.clearCache(),e.refreshOptions(!1);const s=e.getValue();s!=null&&s!==""&&(!Array.isArray(s)||s.length>0)&&e.setValue(s,!0)}};v.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(e=>{if(e.tomselect)return;const l={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["remove_button"],dropdownParent:"body"};e.dataset.renderSubtype==="true"&&(l.render={option:function(s,o){const t=s.subtype||s.email||"";return`
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
                    `}});const a=new TomSelect(e,l);e.dataset.renderSubtype==="true"&&e.tagName==="SELECT"&&E(a,e),h(a,e)}),v.querySelectorAll("select.tom-select, input.tom-select").forEach(e=>{if(e.tomselect)return;const l=e.dataset.sort!="0",a={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(o,t){const r=o.subtype||o.email||"";return`
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
                    `}}};l&&(a.sortField={field:"text",direction:"asc"});const s=new TomSelect(e,a);E(s,e),e.multiple&&(I(s,e),b(s)),h(s,e)}),v.querySelectorAll("select.tom-select-tags, input.tom-select-tags, select.tom-select-add").forEach(e=>{if(e.tomselect)return;const l=e.dataset.placeholder||"Search or add tags",a=e.dataset.maxItems||null,s=new TomSelect(e,{plugins:["remove_button"],maxItems:a,persist:!1,dropdownParent:"body",createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:l,create:e.disabled?!1:o=>{const t=String(o||"").trim();return{value:t,text:t}},createFilter(o){const t=g(o);return t?!Object.values(this.options).some(r=>g(r?.text??r?.value??"")===t):!1},score(o){const t=g(o);return function(r){const n=g(r.text);return t?n===t?2:n.includes(t)?1:0:1}},render:{option(o,t){return`
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
                        `}}});h(s,e)}),v.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(e=>{if(e.tomselect)return;const l=()=>!e||e.tagName!=="SELECT"?[]:Array.from(e.options).filter(t=>t.selected&&String(t.value??"")!=="").map(t=>String(t.value)),a=l();console.log("[TomSelect] BEFORE INIT",e.name,a);const s=new TomSelect(e,{plugins:["remove_button","dropdown_input"],maxItems:null,searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(t,r){const n=t.subtype||t.email||"";return`
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
                    `}}});console.log("[TomSelect] AFTER INIT",e.name,{nativeSelected:l(),tomSelectItems:s.items,tomSelectValue:s.getValue()}),E(s,e);const o=()=>{if(!a.length)return;const t=a.filter(r=>Object.prototype.hasOwnProperty.call(s.options,r));if(console.log("[TomSelect] RESTORE",e.name,{initialSelectedValues:a,validValues:t,options:Object.keys(s.options),before:s.items}),!t.length){console.warn("[TomSelect] Selected values are missing from Tom Select options",{name:e.name,initialSelectedValues:a,options:s.options});return}s.setValue(t,!0),console.log("[TomSelect] RESTORED",e.name,{items:s.items,value:s.getValue()})};o(),setTimeout(()=>{o()},0),requestAnimationFrame(()=>{o()}),b(s),h(s,e)}),v.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(e=>{if(e.tomselect)return;const l=e.dataset.sort!="0",a=e.dataset.route,s={create:!1,persist:!1,hideDropdownArrow:!1,allowEmptyOption:e.dataset.allowEmptyOption==="true"||e.querySelector('option[value=""]')!==null,placeholder:e.dataset.placeholder||e.getAttribute("placeholder")||void 0,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],sortField:l?{field:"text",direction:"asc"}:null,dropdownParent:"body",render:{option:function(t,r){const n=t.subtype||t.email||t.project_code||"";return`
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
                    `}},load:function(t,r){const n=e.dataset.route||a;if(!n)return r();const m=n.includes("?")?"&":"?";let S=`${n}${m}q=${encodeURIComponent(t||"")}`;e.dataset.userId&&(S+=`&user_id=${encodeURIComponent(e.dataset.userId)}`),e.dataset.excludeId&&(S+=`&exclude_id=${encodeURIComponent(e.dataset.excludeId)}`),fetch(S).then(f=>f.json()).then(f=>{const c=Array.isArray(f)?f:f.data||[];r(c.map(p=>({value:String(p.value??p.id),text:p.text??p.name,subtype:p.subtype??p.project_code??""})))}).catch(()=>r())}},o=new TomSelect(e,s);E(o,e),h(o,e)}),document.dispatchEvent(new Event("tomselect:ready"))}const C=(v,g)=>{const b=document.getElementById(v);!b||!b.tomselect||(g?b.tomselect.setValue(g):b.tomselect.clear())};export{C as a,k as i};
