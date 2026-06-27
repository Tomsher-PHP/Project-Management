function m(i=document){const d=(t="")=>String(t).trim().toLowerCase(),c=(t,a)=>{!t?.wrapper||!t?.control||!a.disabled||(t.wrapper.classList.add("opacity-100"),t.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-300"),t.control.classList.remove("bg-white"),t.control.querySelectorAll(".item, input, .ts-control > div").forEach(r=>{r.classList.add("text-bgray-600","dark:text-bgray-300")}))};i.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(t=>{if(t.tomselect)return;const a={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["remove_button"],dropdownParent:"body"};t.dataset.renderSubtype==="true"&&(a.render={option:function(o,e){return`
                        <div>
                            <div class="font-medium">${e(o.text)}</div>
                            <div class="text-sm text-gray-600">${e(o.subtype||"")}</div>
                        </div>
                    `},item:function(o,e){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${e(o.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${e(o.subtype||"")}</span>
                        </div>
                    `}});const r=new TomSelect(t,a);if(t.dataset.renderSubtype==="true"&&t.tagName==="SELECT"){Array.from(t.options).forEach(e=>{const s=String(e.value??"");if(!Object.prototype.hasOwnProperty.call(r.options,s))return;let n=e.dataset.subtype||"";if(!n&&e.dataset.data)try{n=JSON.parse(e.dataset.data)?.subtype||""}catch{n=""}r.options[s]={...r.options[s],subtype:n}}),r.clearCache(),r.refreshOptions(!1);const o=r.getValue();o!=null&&o!==""&&r.setValue(o,!0)}c(r,t)}),i.querySelectorAll("select.tom-select, input.tom-select").forEach(t=>{if(t.tomselect)return;const a=t.dataset.sort!="0",r={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],searchField:["text","subtype"],dropdownParent:"body",render:{option:function(e,s){return`
                        <div>
                            <div class="font-medium">${s(e.text)}</div>
                            <div class="text-sm text-gray-600">${s(e.subtype||"")}</div>
                        </div>
                    `},item:function(e,s){return`
                        <div>
                            <span class="font-medium">${s(e.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${s(e.subtype||"")}</span>
                        </div>
                    `}}};a&&(r.sortField={field:"text",direction:"asc"});const o=new TomSelect(t,r);c(o,t)}),i.querySelectorAll("select.tom-select-tags, input.tom-select-tags, select.tom-select-add").forEach(t=>{if(t.tomselect)return;const a=t.dataset.placeholder||"Search or add tags",r=t.dataset.maxItems||null,o=new TomSelect(t,{plugins:["remove_button"],maxItems:r,persist:!1,dropdownParent:"body",createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:a,create:t.disabled?!1:e=>{const s=String(e||"").trim();return{value:s,text:s}},createFilter(e){const s=d(e);return s?!Object.values(this.options).some(n=>d(n?.text??n?.value??"")===s):!1},score(e){const s=d(e);return function(n){const l=d(n.text);return s?l===s?2:l.includes(s)?1:0:1}},render:{option(e,s){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${s(e.text)}</span>
                            ${e.$option?"":'<span class="text-xs font-semibold text-success-400">Create</span>'}
                        </div>
                    `},item(e,s){return`<div class="font-medium">${s(e.text)}</div>`}}});c(o,t)}),i.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(t=>{if(t.tomselect)return;const a=new TomSelect(t,{plugins:["remove_button","dropdown_input"],maxItems:null,dropdownParent:"body"});c(a,t)}),i.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(t=>{if(t.tomselect)return;const a=t.dataset.sort!="0",r=t.dataset.route,o={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","remove_button"],sortField:a?{field:"text",direction:"asc"}:null,dropdownParent:"body",load:function(s,n){if(!s.length)return n();fetch(`${r}?q=${encodeURIComponent(s)}`).then(l=>l.json()).then(l=>{n(l.map(u=>({value:u.id,text:u.name})))}).catch(()=>n())}},e=new TomSelect(t,o);c(e,t)}),document.dispatchEvent(new Event("tomselect:ready"))}const p=(i,d)=>{const c=document.getElementById(i);!c||!c.tomselect||(d?c.tomselect.setValue(d):c.tomselect.clear())};export{p as a,m as i};
