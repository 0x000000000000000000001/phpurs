const events=[];
export const opaque=value=>value;
export const newCallback=()=>acc=>{
  events.push('first:'+acc);
  return item=>{events.push('second:'+item);return acc+item;};
};
export const readEvents=()=>events.join(',');
