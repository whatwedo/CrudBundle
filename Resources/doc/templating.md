
```

crud_show
crud_create
crud_edit

```
- sind diese notwendig?


```
crud|<block_prefix>_block


twig_function: crud_content_row
block: crud_content|<block_prefix>_row

 
block: crud_content|<block_prefix>_show_row

   twig_function: curd_content_label
   block: crud_content|<block_prefix>_show_label
   
   twig_function: curd_content_value
   block: crud_content|<block_prefix>_show_value
  
   
block: crud_content|<block_prefix>_edit_row
    
    twig_function: curd_content_label
    block: crud_content|<block_prefix>_edit_label
    
    twig_function: curd_content_form
    block: crud_content|<block_prefix>_edit_form
    
    twig_function: curd_content_form
    block: crud_content|<block_prefix>_edit_help
    
    
block: crud_content|<block_prefix>_create_row
    twig_function: curd_content_label
    block: crud_content|<block_prefix>_create_label
    
    twig_function: curd_content_form
    block: crud_content|<block_prefix>_create_form
    
    twig_function: curd_content_help
    block: crud_content|<block_prefix>_create_help
```


- brauch es `crud_block`
- edit und create ist es das selbe?
- edit und create fallbacks?

