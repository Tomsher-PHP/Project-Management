function m(l=document){const i=(t="")=>String(t).trim().toLowerCase(),a=(t,o)=>{!t?.wrapper||!t?.control||!o.disabled||(t.wrapper.classList.add("opacity-100"),t.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-200"),t.control.classList.remove("bg-white"),t.control.querySelectorAll(".item, input, .ts-control > div").forEach(s=>{s.classList.add("text-bgray-600","dark:text-bgray-200")}))};l.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(t=>{if(t.tomselect)return;const o={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["clear_button"]};t.dataset.renderSubtype==="true"&&(o.render={option:function(e,r){return`
                        <div>
                            <div class="font-medium">${r(e.text)}</div>
                            <div class="text-sm text-gray-600">${r(e.subtype||"")}</div>
                        </div>
                    `},item:function(e,r){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${r(e.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${r(e.subtype||"")}</span>
                        </div>
                    `}});const s=new TomSelect(t,o);if(t.dataset.renderSubtype==="true"&&t.tagName==="SELECT"){Array.from(t.options).forEach(r=>{const n=String(r.value??"");if(!Object.prototype.hasOwnProperty.call(s.options,n))return;let c=r.dataset.subtype||"";if(!c&&r.dataset.data)try{c=JSON.parse(r.dataset.data)?.subtype||""}catch{c=""}s.options[n]={...s.options[n],subtype:c}}),s.clearCache(),s.refreshOptions(!1);const e=s.getValue();e!=null&&e!==""&&s.setValue(e,!0)}a(s,t)}),l.querySelectorAll("select.tom-select, input.tom-select").forEach(t=>{if(t.tomselect)return;const o=t.dataset.sort!="0",s={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","clear_button","remove_button"],searchField:["text","subtype"],render:{option:function(r,n){return`
                        <div>
                            <div class="font-medium">${n(r.text)}</div>
                            <div class="text-sm text-gray-600">${n(r.subtype||"")}</div>
                        </div>
                    `},item:function(r,n){return`
                        <div>
                            <span class="font-medium">${n(r.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${n(r.subtype||"")}</span>
                        </div>
                    `}}};o&&(s.sortField={field:"text",direction:"asc"});const e=new TomSelect(t,s);a(e,t)}),l.querySelectorAll("select.tom-select-tags, input.tom-select-tags").forEach(t=>{if(t.tomselect)return;const o=new TomSelect(t,{plugins:["remove_button","clear_button"],maxItems:null,persist:!1,createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:"Search or add tags",create:t.disabled?!1:s=>{const e=String(s||"").trim();return{value:e,text:e}},createFilter(s){const e=i(s);return e?!Object.values(this.options).some(r=>i(r?.text??r?.value??"")===e):!1},score(s){const e=i(s);return function(r){const n=i(r.text);return e?n===e?2:n.includes(e)?1:0:1}},render:{option(s,e){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${e(s.text)}</span>
                            ${s.$option?"":'<span class="text-xs font-semibold text-success-400">Create</span>'}
                        </div>
                    `},item(s,e){return`<div class="font-medium">${e(s.text)}</div>`}}});a(o,t)}),l.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(t=>{if(t.tomselect)return;const o=new TomSelect(t,{plugins:["remove_button","dropdown_input","clear_button"],maxItems:null});a(o,t)}),l.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(t=>{if(t.tomselect)return;const o=t.dataset.sort!="0",s=t.dataset.route,e={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","clear_button"],sortField:o?{field:"text",direction:"asc"}:null,load:function(n,c){if(!n.length)return c();fetch(`${s}?q=${encodeURIComponent(n)}`).then(u=>u.json()).then(u=>{c(u.map(d=>({value:d.id,text:d.name})))}).catch(()=>c())}},r=new TomSelect(t,e);a(r,t)}),document.dispatchEvent(new Event("tomselect:ready"))}const p=(l,i)=>{const a=document.getElementById(l);!a||!a.tomselect||(i?a.tomselect.setValue(i):a.tomselect.clear())};export{p as a,m as i};
