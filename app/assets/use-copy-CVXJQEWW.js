import{c as a}from"./index-C-2a0Dur.js";import{r as t}from"./vendor-CIGJ9g2q.js";/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const n=[["path",{d:"M8 2v4",key:"1cmpym"}],["path",{d:"M16 2v4",key:"4m81vk"}],["rect",{width:"18",height:"18",x:"3",y:"4",rx:"2",key:"1hopcy"}],["path",{d:"M3 10h18",key:"8toen8"}]],y=a("calendar",n);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const l=[["rect",{width:"14",height:"14",x:"8",y:"8",rx:"2",ry:"2",key:"17jyea"}],["path",{d:"M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2",key:"zix9uf"}]],h=a("copy",l);/**
 * @license lucide-react v0.511.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=[["path",{d:"M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8",key:"v9h5vc"}],["path",{d:"M21 3v5h-5",key:"1q7to0"}],["path",{d:"M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16",key:"3uifl3"}],["path",{d:"M8 16H3v5",key:"1cv678"}]],f=a("refresh-cw",p),o=typeof navigator<"u"&&typeof navigator.clipboard<"u";function v(){const[s,e]=t.useState({success:!1,error:null}),c=t.useCallback(async r=>{if(!o){e({success:!1,error:"Clipboard is not available"});return}if(!r.trim()){e({success:!1,error:"Cannot copy empty or whitespace text"});return}try{await navigator.clipboard.writeText(r),e({success:!0,error:null})}catch{e({success:!1,error:"Failed to copy"})}},[]),i=t.useCallback(async()=>{if(!o)return e({success:!1,error:"Clipboard is not available"}),"";try{const r=await navigator.clipboard.readText();return r.trim()?(e({success:!0,error:null}),r):""}catch{return e({success:!1,error:"Failed to paste"}),""}},[]);return{copyToClipboard:c,pasteFromClipboard:i,state:s}}export{h as C,f as R,y as a,v as u};
