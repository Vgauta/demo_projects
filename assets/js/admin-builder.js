(function($){
  const example = {layers:[{id:'base',title:'Base Color',type:'color',order:10,options:[{id:'white',title:'White',color:'#ffffff',price:0,image:'',conditions:[]},{id:'black',title:'Black',color:'#111111',price:5,image:'',conditions:[]}]},{id:'accent',title:'Accent',type:'color',order:20,options:[{id:'gold',title:'Gold',color:'#d9a441',price:7,conditions:[{field:'base',equals:'black'}]},{id:'blue',title:'Blue',color:'#2364aa',price:3,conditions:[]}]},{id:'custom_text',title:'Custom Text',type:'text',order:30,price:8,placeholder:'Your name',conditions:[]},{id:'logo',title:'Logo Upload',type:'upload',order:40,price:12,conditions:[]}]};
  $('#cwpc-load-example').on('click',()=>$('#cwpc-schema').val(JSON.stringify(example,null,2)));
  $('#cwpc-format-json').on('click',()=>{try{$('#cwpc-schema').val(JSON.stringify(JSON.parse($('#cwpc-schema').val()),null,2));}catch(e){alert('Invalid JSON: '+e.message);}});
})(jQuery);
