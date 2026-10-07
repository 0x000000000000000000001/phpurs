const events=[];
let threshold=2;
export const opaque=value=>value;
export const newCallback=()=>acc=>{
  events.push('first:'+acc);
  return item=>{events.push('second:'+item);return acc+item;};
};
export const newPredicate=()=>item=>{events.push('visit:'+item);return item>threshold;};
export const setThreshold=value=>()=>{threshold=value;};
export const readEvents=()=>events.join(',');
