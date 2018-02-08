Bolt Searchable Content Extension
======================

An easy to use extension to search for Repeaters and Blocks fields content.

### Configuration

We add a textarea field to the Contenttype we want its Repetears or Blocks content to be searchable in the `contenttypes.yml`

```
pages:
    name: Pages
    singular_name: Page
    fields:
        ...
        myrepeater:
            type: repeater
            fields:
                title_text:
                    type: text
                body_text:
                    type: html
                description_text:
                    type: html
        myblock:
            type: block
            label: My Blocks
            fields:
                myblocktext:
                    label: Text block
                    fields:
                        title:
                            type: text
                        description:
                            type: html
                        more_text:
                            type: html
                            height: 70px
                myblockbody:
                    label: Paragraph
                    fields:
                        heading:
                            type: text
                        content:
                            type: html
                            height: 100px
        search:
                label: Searchable content
                postfix: "<p>Extra index for the website search engine. This is automatically overwritten.</p>"
                type: textarea
                height: 300px
        ...
```  

 We add the Repeaters or Blocks fields to our extension's config file `searchablecontent.twokings.yml`. This needs to match the following format:
 - contenttype -> field (type: repeater) -> subField
 - contenttype -> field (type: block) -> blockName -> subField
 
 ```
 searchable:
    pages:
        myrepeater:
            - title_text
            - body_text
            - description_text
        myblock:
            myblocktext:
                - title
                - description
                - more_text
            myblockbody:
                - heading
                - content
    anothercontenttype:
        ...
 ```
 
 ### Usage
 
 There are two options:
 
 #### 1. **Making the content of a single record searchable.** 
This will happen automatically after clicking the Save button when editing the content of a Record. After the Record has been saved we can refresh the edit page and we will see the content has added to the `search` field we added to the Contenttype.
 
 #### 2. **Making the content of all Records searchable.**
If we want all the Records of the Contenttypes we added to `searchablecontent.twokings.yml` to be searchable we need to visit the following url: `yourdomain/yourboltbackendpath/extensions/searchablecontent/searchable`. This will run a script that will update the `search` field of all the Records giving us details at the end of the performed operation.
