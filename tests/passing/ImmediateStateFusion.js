const inputs=[];
let calls=0;
export const opaque=x=>x;
export const countedDepth=x=>{inputs.push('depth');return x;};
export const countedInitial=x=>{inputs.push('initial');return x;};
export const readInputs=()=>inputs.join(',');
export const observedStep=x=>{calls++;return x+2;};
export const readCalls=()=>calls;
export const invoke=f=>initial=>f(initial).store;
