<form action="category_edit.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="category_id" value="{$category->category_id}">
    <div class="bigtable">
        <fieldset class="cssform">
            <legend class="ui-state-active ui-corner-top">{_T string="Category"}</legend>
            <div>
                <p>
                    <span class="bline">{_T string="Name"}</span>
                    <input type="text" name="name" size="60" maxlength="100" value="{$category->name}" required>
                </p>
            </div>
            <div>
                <p>
                    <label for="is_active" class="bline">{_T string="Is active"}</label>
                    <input type="checkbox" name="is_active" id="is_active" value="true"{if $category->is_active} checked="checked"{/if}>
                </p>
            </div>
            <div>
                <p>
                </p>
            </div>
            <div>
                <p>
                    <span class="bline">{_T string="Picture:"}</span>
                    <img src="picture.php?category_id={$category->category_id}&amp;rand={$time}&thumb=1"
                        class="picture"
                        width="{$category->picture->getOptimalThumbWidth()}"
                        height="{$category->picture->getOptimalThumbHeight()}"
                        alt="{_T string="Category photo"}"/><br/>
    {if $category->picture->hasPicture()}
                    <input type="checkbox" name="del_picture" id="del_picture" value="1"/><span class="labelalign"><label for="del_picture">{_T string="Delete image"}</label></span><br/>
    {/if}

                    <input class="labelalign" type="file" name="picture"/>
                </p>
            </div>
        </fieldset>
    </div>
    <div class="button-container">
        <input type="submit" id="btnsave" name="save" value="{_T string="Save"}">
        <a href="categories_list.php?msg=canceled" class="button" id="btncancel">{_T string="Cancel"}</a>
    </div>
</form>
