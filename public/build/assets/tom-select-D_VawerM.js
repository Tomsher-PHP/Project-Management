function m(i=document){const u=(t="")=>String(t).trim().toLowerCase(),c=(t,a)=>{!t?.wrapper||!t?.control||!a.disabled||(t.wrapper.classList.add("opacity-100"),t.control.classList.add("border-bgray-200","bg-bgray-50","text-bgray-600","dark:border-darkblack-400","dark:bg-darkblack-500","dark:text-bgray-200"),t.control.classList.remove("bg-white"),t.control.querySelectorAll(".item, input, .ts-control > div").forEach(r=>{r.classList.add("text-bgray-600","dark:text-bgray-200")}))};i.querySelectorAll("select.tom-select-no-search, input.tom-select-no-search").forEach(t=>{if(t.tomselect)return;const a={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["clear_button"]};t.dataset.renderSubtype==="true"&&(a.render={option:function(n,e){return`
                        <div>
                            <div class="font-medium">${e(n.text)}</div>
                            <div class="text-sm text-gray-600">${e(n.subtype||"")}</div>
                        </div>
                    `},item:function(n,e){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${e(n.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${e(n.subtype||"")}</span>
                        </div>
                    `}});const r=new TomSelect(t,a);if(t.dataset.renderSubtype==="true"&&t.tagName==="SELECT"){Array.from(t.options).forEach(e=>{const s=String(e.value??"");if(!Object.prototype.hasOwnProperty.call(r.options,s))return;let o=e.dataset.subtype||"";if(!o&&e.dataset.data)try{o=JSON.parse(e.dataset.data)?.subtype||""}catch{o=""}r.options[s]={...r.options[s],subtype:o}}),r.clearCache(),r.refreshOptions(!1);const n=r.getValue();n!=null&&n!==""&&r.setValue(n,!0)}c(r,t)}),i.querySelectorAll("select.tom-select, input.tom-select").forEach(t=>{if(t.tomselect)return;const a=t.dataset.sort!="0",r={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","clear_button","remove_button"],searchField:["text","subtype"],render:{option:function(e,s){return`
                        <div>
                            <div class="font-medium">${s(e.text)}</div>
                            <div class="text-sm text-gray-600">${s(e.subtype||"")}</div>
                        </div>
                    `},item:function(e,s){return`
                        <div>
                            <span class="font-medium">${s(e.text)}</span>
                            <span class="text-sm text-gray-600 ml-2">${s(e.subtype||"")}</span>
                        </div>
                    `}}};a&&(r.sortField={field:"text",direction:"asc"});const n=new TomSelect(t,r);c(n,t)}),i.querySelectorAll("select.tom-select-tags, input.tom-select-tags, select.tom-select-add").forEach(t=>{if(t.tomselect)return;const a=t.dataset.placeholder||"Search or add tags",r=t.dataset.maxItems||null,n=new TomSelect(t,{plugins:["remove_button","clear_button"],maxItems:r,persist:!1,createOnBlur:!0,hideSelected:!0,closeAfterSelect:!1,placeholder:a,create:t.disabled?!1:e=>{const s=String(e||"").trim();return{value:s,text:s}},createFilter(e){const s=u(e);return s?!Object.values(this.options).some(o=>u(o?.text??o?.value??"")===s):!1},score(e){const s=u(e);return function(o){const l=u(o.text);return s?l===s?2:l.includes(s)?1:0:1}},render:{option(e,s){return`
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${s(e.text)}</span>
                            ${e.$option?"":'<span class="text-xs font-semibold text-success-400">Create</span>'}
                        </div>
                    `},item(e,s){return`<div class="font-medium">${s(e.text)}</div>`}}});c(n,t)}),i.querySelectorAll("select.tom-select-multiple, input.tom-select-multiple").forEach(t=>{if(t.tomselect)return;const a=new TomSelect(t,{plugins:["remove_button","dropdown_input","clear_button"],maxItems:null});c(a,t)}),i.querySelectorAll("select.tom-select-lazy, input.tom-select-lazy").forEach(t=>{if(t.tomselect)return;const a=t.dataset.sort!="0",r=t.dataset.route,n={create:!1,persist:!1,hideDropdownArrow:!1,plugins:["dropdown_input","clear_button"],sortField:a?{field:"text",direction:"asc"}:null,load:function(s,o){if(!s.length)return o();fetch(`${r}?q=${encodeURIComponent(s)}`).then(l=>l.json()).then(l=>{o(l.map(d=>({value:d.id,text:d.name})))}).catch(()=>o())}},e=new TomSelect(t,n);c(e,t)}),document.dispatchEvent(new Event("tomselect:ready"))}const p=(i,u)=>{const c=document.getElementById(i);!c||!c.tomselect||(u?c.tomselect.setValue(u):c.tomselect.clear())};export{p as a,m as i};
