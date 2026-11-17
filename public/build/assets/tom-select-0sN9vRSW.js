function k(b=document){const h=(e="")=>String(e).trim().toLowerCase(),x=e=>{const n="ts-selection-count",l="ts-selection-item-hidden";let o=null;e.wrapper.classList.add("ts-wrapper-multiple-compact");const s=()=>{e.control.querySelector(`.${n}`)?.setAttribute("aria-expanded","false"),o&&(o.remove(),o=null,document.removeEventListener("mousedown",t),document.removeEventListener("keydown",r),window.removeEventListener("resize",s))},t=d=>{o?.contains(d.target)||d.target instanceof Element&&d.target.closest(`.${n}`)||s()},r=d=>{d.key==="Escape"&&(s(),e.focus())},i=d=>{o&&(!(d.target instanceof Element)||!d.target.closest(`.${n}`))&&s()},u=()=>{if(!o)return;const d=o.querySelector(".ts-selected-items-list"),m=d.scrollTop,a=new Map(Array.from(d.children).map(p=>[p.dataset.value,p]));e.items.forEach(p=>{const c=String(p);let g=a.get(c);if(!g){g=document.createElement("div"),g.className="ts-selected-items-option",g.dataset.value=c;const S=document.createElement("span");S.className="ts-selected-items-label";const w=document.createElement("button");w.type="button",w.className="ts-selected-items-remove",w.textContent="×",w.addEventListener("click",A=>{const C=e.getItem(c);e.isLocked||!C||!e.shouldDelete([C],A)||(e.removeItem(C),e.refreshOptions(!1),e.inputState())}),g.append(S,w)}const v=g.querySelector(".ts-selected-items-label"),L=g.querySelector(".ts-selected-items-remove");v.textContent=String(e.options[c]?.text??c),L.setAttribute("aria-label",`Remove ${v.textContent}`),d.append(g),a.delete(c)}),a.forEach(p=>p.remove()),d.scrollTop=m},f=d=>{const m=e.ignoreFocus;try{e.ignoreFocus=!0,e.close()}finally{e.ignoreFocus=m}s(),o=document.createElement("div"),o.className="ts-selected-items-popover",o.setAttribute("role","dialog"),o.setAttribute("aria-label","Selected items"),o.innerHTML=`
                <div class="ts-selected-items-heading">Selected items</div>
                <div class="ts-selected-items-list"></div>
            `,document.body.append(o),o.addEventListener("mousedown",v=>{v.stopPropagation()}),o.addEventListener("click",v=>{v.stopPropagation()});const a=e.wrapper.getBoundingClientRect(),p=8,c=Math.min(a.width,window.innerWidth-p*2),g=Math.min(Math.max(p,a.left),window.innerWidth-c-p);o.style.width=`${c}px`,o.style.left=`${g+window.scrollX}px`,o.style.top=`${a.bottom+window.scrollY+4}px`,u(),document.addEventListener("mousedown",t),document.addEventListener("keydown",r),window.addEventListener("resize",s),o.querySelector(".ts-selected-items-remove")?.focus(),d.setAttribute("aria-expanded","true")},y=()=>{const d=Array.from(e.control.querySelectorAll(".item")),m=Math.max(0,d.length-1);d.forEach((p,c)=>{p.classList.toggle(l,c>0)});let a=e.control.querySelector(`.${n}`);if(m===0){a?.remove(),u();return}if(!a){a=document.createElement("button"),a.type="button",a.className=n,a.setAttribute("aria-haspopup","dialog"),a.setAttribute("aria-expanded","false"),a.addEventListener("mousedown",c=>{c.preventDefault()}),a.addEventListener("click",c=>{c.preventDefault(),c.stopPropagation(),e.isDisabled||f(a)});const p=e.control.querySelector("input");e.control.insertBefore(a,p)}a.textContent=`+${m}`,a.setAttribute("aria-label",`Show all ${d.length} selected items`),a.disabled=e.isDisabled,u()};e.on("item_add",y),e.on("item_remove",y),e.on("change",y),e.control.addEventListener("mousedown",i),e.on("destroy",()=>{e.control.removeEventListener("mousedown",i),s()}),y()},E=(e,n)=>{!e?.wrapper||!e?.control||!n.disabled||(e.wrapper.classList.add("opacity-100"),e.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-300"),e.control.classList.remove("bg-white"),e.control.querySelectorAll(".item, input, .ts-control > div").forEach(l=>{l.classList.add("text-bgray-600","dark:text-bgray-300")}))},$=e=>{const n=e?.dropdown,l=e?.dropdown_content;if(!n||!l)return;const o=8,s=l.style.maxHeight;let t=null,r=!1;const i=()=>{l.style.maxHeight=s},u=()=>{if(t=null,!e.isOpen||!n.isConnected)return;n.classList.add("ts-dropdown-auto-position"),n.classList.remove("ts-dropdown-up"),i(),e.positionDropdown();const a=e.control.getBoundingClientRect(),p=window.getComputedStyle(n),c=Number.parseFloat(p.marginTop)||0,g=Math.max(0,window.innerHeight-a.bottom-o-c),v=Math.max(0,a.top-o-c),L=n.getBoundingClientRect().height,S=L>g&&v>g,w=S?v:g;if(L>w){const A=l.getBoundingClientRect().height,C=Math.max(0,L-A),O=Math.max(0,Math.floor(w-C));l.style.maxHeight=`${O}px`}if(n.classList.toggle("ts-dropdown-up",S),S){const A=n.getBoundingClientRect().height;n.style.top=`${a.top+window.scrollY-A-c}px`}},f=()=>{!e.isOpen||t!==null||(t=window.requestAnimationFrame(u))},y=()=>{r||(r=!0,window.addEventListener("resize",f,{passive:!0}),window.addEventListener("scroll",f,{capture:!0,passive:!0}))},d=()=>{r&&(r=!1,window.removeEventListener("resize",f),window.removeEventListener("scroll",f,!0),t!==null&&(window.cancelAnimationFrame(t),t=null))},m=()=>{d(),i(),n.classList.remove("ts-dropdown-auto-position","ts-dropdown-up")};e.on("dropdown_open",()=>{y(),u()}),e.on("dropdown_close",m),e.on("type",f),e.on("load",f),e.on("option_add",f),e.on("item_add",f),e.on("item_remove",f),e.on("destroy",m)},I=(e,n)=>{if(!n||n.tagName!=="SELECT"||!n.multiple)return;const l=Array.from(n.options).filter(s=>s.selected&&String(s.value??"")!=="");if(!l.length)return;const o=l.map(s=>String(s.value));l.forEach(s=>{const t=String(s.value);if(!Object.prototype.hasOwnProperty.call(e.options,t)){let r=s.dataset.subtype||"",i=s.dataset.email||"";if(s.dataset.data)try{const u=JSON.parse(s.dataset.data);!r&&u?.subtype&&(r=u.subtype),!r&&u?.email&&(r=u.email),!i&&u?.email&&(i=u.email)}catch{}e.addOption({value:t,text:s.textContent.trim(),subtype:r||i,email:i||r})}}),e.setValue(o,!0)},T=(e,n)=>{if(!n||n.tagName!=="SELECT")return;let l=!1;if(Array.from(n.options).forEach(o=>{const s=String(o.value??"");if(!Object.prototype.hasOwnProperty.call(e.options,s))return;let t=o.dataset.subtype||"",r=o.dataset.email||"";if(o.dataset.data)try{const i=JSON.parse(o.dataset.data);!t&&i?.subtype&&(t=i.subtype),!t&&i?.email&&(t=i.email),!r&&i?.email&&(r=i.email)}catch{}(t||r)&&(l=!0,e.options[s]={...e.options[s],subtype:t||r,email:r||t})}),l){e.clearCache(),e.refreshOptions(!1);const o=e.getValue();o!=null&&o!==""&&(!Array.isArray(o)||o.length>0)&&e.setValue(o,!0)}};b.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(e=>{if(e.tomselect)return;const n={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["remove_button"],dropdownParent:"body"};e.dataset.renderSubtype==="true"&&(n.render={option:function(o,s){const t=o.subtype||o.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${s(o.text)}
                            </div>

                            ${t?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${s(t)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(o,s){const t=o.subtype||o.email||"";return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">
                                ${s(o.text)}
                            </span>

                            ${t?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${s(t)}
                                        </span>
                                    `:""}
                        </div>
                    `}});const l=new TomSelect(e,n);$(l),e.dataset.renderSubtype==="true"&&e.tagName==="SELECT"&&T(l,e),E(l,e)}),b.querySelectorAll("select.tom-select, input.tom-select").forEach(e=>{if(e.tomselect)return;const n=e.dataset.sort!="0",l={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(s,t){const r=s.subtype||s.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${t(s.text)}
                            </div>

                            ${r?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${t(r)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(s,t){const r=s.subtype||s.email||"";return`
                        <div>
                            <span class="font-medium">
                                ${t(s.text)}
                            </span>

                            ${r?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${t(r)}
                                        </span>
                                    `:""}
                        </div>
                    `}}};n&&(l.sortField={field:"text",direction:"asc"});const o=new TomSelect(e,l);$(o),T(o,e),e.multiple&&(I(o,e),x(o)),E(o,e)}),b.querySelectorAll("select.tom-select-tags, input.tom-select-tags, select.tom-select-add").forEach(e=>{if(e.tomselect)return;const n=e.dataset.placeholder||"Search or add tags",l=e.dataset.maxItems||null,o=new TomSelect(e,{plugins:["remove_button"],maxItems:l,persist:!1,dropdownParent:"body",createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:n,create:e.disabled?!1:s=>{const t=String(s||"").trim();return{value:t,text:t}},createFilter(s){const t=h(s);return t?!Object.values(this.options).some(r=>h(r?.text??r?.value??"")===t):!1},score(s){const t=h(s);return function(r){const i=h(r.text);return t?i===t?2:i.includes(t)?1:0:1}},render:{option(s,t){return`
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium">
                                    ${t(s.text)}
                                </span>

                                ${s.$option?"":`
                                            <span class="text-xs font-semibold text-success-400">
                                                Create
                                            </span>
                                        `}
                            </div>
                        `},item(s,t){return`
                            <div class="font-medium">
                                ${t(s.text)}
                            </div>
                        `}}});$(o),E(o,e)}),b.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(e=>{if(e.tomselect)return;const n=()=>!e||e.tagName!=="SELECT"?[]:Array.from(e.options).filter(t=>t.selected&&String(t.value??"")!=="").map(t=>String(t.value)),l=n();console.log("[TomSelect] BEFORE INIT",e.name,l);const o=new TomSelect(e,{plugins:["remove_button","dropdown_input"],maxItems:null,searchField:["text","subtype","email"],dropdownParent:"body",render:{option:function(t,r){const i=t.subtype||t.email||"";return`
                        <div>
                            <div class="font-medium">
                                ${r(t.text)}
                            </div>

                            ${i?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            ${r(i)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(t,r){const i=t.subtype||t.email||"";return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">
                                ${r(t.text)}
                            </span>

                            ${i?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${r(i)}
                                        </span>
                                    `:""}
                        </div>
                    `}}});$(o),console.log("[TomSelect] AFTER INIT",e.name,{nativeSelected:n(),tomSelectItems:o.items,tomSelectValue:o.getValue()}),T(o,e);const s=()=>{if(!l.length)return;const t=l.filter(r=>Object.prototype.hasOwnProperty.call(o.options,r));if(console.log("[TomSelect] RESTORE",e.name,{initialSelectedValues:l,validValues:t,options:Object.keys(o.options),before:o.items}),!t.length){console.warn("[TomSelect] Selected values are missing from Tom Select options",{name:e.name,initialSelectedValues:l,options:o.options});return}o.setValue(t,!0),console.log("[TomSelect] RESTORED",e.name,{items:o.items,value:o.getValue()})};s(),setTimeout(()=>{s()},0),requestAnimationFrame(()=>{s()}),x(o),E(o,e)}),b.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(e=>{if(e.tomselect)return;const n=e.dataset.sort!="0",l=e.dataset.route,o={create:!1,persist:!1,hideDropdownArrow:!1,allowEmptyOption:e.dataset.allowEmptyOption==="true"||e.querySelector('option[value=""]')!==null,placeholder:e.dataset.placeholder||e.getAttribute("placeholder")||void 0,plugins:["dropdown_input","remove_button"],searchField:["text","subtype","email"],sortField:n?{field:"text",direction:"asc"}:null,dropdownParent:"body",render:{option:function(t,r){const i=t.subtype||t.email||t.project_code||"";return`
                        <div>
                            <div class="font-medium">
                                ${r(t.text||t.name)}
                            </div>

                            ${i?`
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                             ${r(i)}
                                        </div>
                                    `:""}
                        </div>
                    `},item:function(t,r){const i=t.subtype||t.email||t.project_code||"";return`
                        <div>
                            <span class="font-medium">
                                ${r(t.text||t.name)}
                            </span>

                            ${i?`
                                        <span class="text-sm text-gray-600 dark:text-gray-400 ml-2">
                                            ${r(i)}
                                        </span>
                                    `:""}
                        </div>
                    `}},load:function(t,r){const i=e.dataset.route||l;if(!i)return r();const u=i.includes("?")?"&":"?";let f=`${i}${u}q=${encodeURIComponent(t||"")}`;e.dataset.userId&&(f+=`&user_id=${encodeURIComponent(e.dataset.userId)}`),e.dataset.excludeId&&(f+=`&exclude_id=${encodeURIComponent(e.dataset.excludeId)}`),fetch(f).then(y=>y.json()).then(y=>{const d=Array.isArray(y)?y:y.data||[];r(d.map(m=>({value:String(m.value??m.id),text:m.text??m.name,subtype:m.subtype??m.project_code??""})))}).catch(()=>r())}},s=new TomSelect(e,o);$(s),T(s,e),E(s,e)}),document.dispatchEvent(new Event("tomselect:ready"))}const P=(b,h)=>{const x=document.getElementById(b);!x||!x.tomselect||(h?x.tomselect.setValue(h):x.tomselect.clear())};export{P as a,k as i};
