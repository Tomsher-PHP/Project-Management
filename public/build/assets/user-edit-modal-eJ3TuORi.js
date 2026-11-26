document.addEventListener("DOMContentLoaded",()=>{function f({containerId:p,addButtonId:$,fieldName:c,idPrefix:o,numberPlaceholder:_}){const l=document.getElementById(p),v=document.getElementById($);if(!l||!v)return;function k(){l.querySelectorAll(".modal-contact-row").forEach((e,r)=>{e.dataset.index=r;const m=e.querySelector(".col-contact-name label"),d=e.querySelector(".col-contact-name input");if(m&&d){const a=`${o}_name_${r}`;m.setAttribute("for",a),d.id=a,d.name=`${c}[${r}][name]`}const y=e.querySelector(".col-contact-number label"),b=e.querySelector(".col-contact-number input");if(y&&b){const a=`${o}_number_${r}`;y.setAttribute("for",a),b.id=a,b.name=`${c}[${r}][number]`}const h=e.querySelector(".col-contact-relation label"),g=e.querySelector(".col-contact-relation input");if(h&&g){const a=`${o}_relation_${r}`;h.setAttribute("for",a),g.id=a,g.name=`${c}[${r}][relation]`}})}function w(t){const e=document.createElement("div");return e.className="modal-contact-row border border-gray-200 dark:border-darkblack-400 rounded-lg p-4 bg-gray-50/50 dark:bg-darkblack-500/50",e.dataset.index=t,e.innerHTML=`
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                    <div class="col-contact-name md:col-span-4 flex flex-col gap-2">
                        <label for="${o}_name_${t}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Contact Name
                        </label>
                        <input type="text"
                               id="${o}_name_${t}"
                               name="${c}[${t}][name]"
                               value=""
                               placeholder="Enter contact name"
                               class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                    </div>

                    <div class="col-contact-number md:col-span-4 flex flex-col gap-2">
                        <label for="${o}_number_${t}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Contact Number
                        </label>
                        <input type="text"
                               id="${o}_number_${t}"
                               name="${c}[${t}][number]"
                               value=""
                               placeholder="${_||"+971501234567"}"
                               class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                    </div>

                    <div class="col-contact-relation md:col-span-3 flex flex-col gap-2">
                        <label for="${o}_relation_${t}" class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                            Relation
                        </label>
                        <input type="text"
                               id="${o}_relation_${t}"
                               name="${c}[${t}][relation]"
                               value=""
                               placeholder="e.g. Father, Brother"
                               class="w-full rounded-lg border border-gray-300 p-2 focus:border focus:border-success-300 focus:ring-0 dark:bg-darkblack-500 dark:text-white dark:border-darkblack-400">
                    </div>

                    <div class="md:col-span-1 flex items-center justify-end md:justify-center pt-8">
                        <button type="button" class="btn-remove-contact inline-flex h-[42px] w-[42px] items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-500 hover:bg-red-100 hover:border-red-300 transition dark:bg-darkblack-600 dark:border-red-900/50" title="Remove Contact" aria-label="Remove contact">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            `,e}v.addEventListener("click",()=>{const e=l.querySelectorAll(".modal-contact-row").length,r=w(e);l.appendChild(r),k()}),l.addEventListener("click",t=>{const e=t.target.closest(".btn-remove-contact");if(!e)return;const r=e.closest(".modal-contact-row");if(!r)return;l.querySelectorAll(".modal-contact-row").length<=1?r.querySelectorAll("input").forEach(d=>d.value=""):(r.remove(),k())})}f({containerId:"modal-emergency-contacts-container",addButtonId:"modal-btn-add-emergency-contact",fieldName:"emergency_contacts",idPrefix:"modal_emergency_contact",numberPlaceholder:"+971501234567"}),f({containerId:"modal-native-contacts-container",addButtonId:"modal-btn-add-native-contact",fieldName:"native_contacts",idPrefix:"modal_native_contact",numberPlaceholder:"+919876543210"});const n=document.getElementById("modal_marital_status"),u=document.getElementById("modal-children-count-wrapper"),s=document.getElementById("modal_children_count");function i(){if(!n||!u)return;n.value==="Married"?u.classList.remove("hidden"):(u.classList.add("hidden"),s&&(s.tomselect?s.tomselect.clear():s.value=""))}n&&(n.addEventListener("change",i),n.tomselect&&n.tomselect.on("change",i),i())});
