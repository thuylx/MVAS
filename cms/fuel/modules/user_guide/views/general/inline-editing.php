<h1>Inline Editing</h1>
<p>Inline editing allows users to quickly modify module information within the context of the website. This is accomplished by using
either the <a href="<?=user_guide_url('helpers/fuel_helper')?>">fuel_edit</a> or the <a href="<?=user_guide_url('helpers/fuel_helper')?>">fuel_var</a> function with the latter
specific to pages that are completely editable (and not just module data).
</p>

<p>For inline editing to work, you must be logged into FUEL and have the proper permissions to edit the page or module information. 
A <span style="background: transparent url(<?=img_path('ico_pencil.png', FUEL_FOLDER)?>) no-repeat; display: inline-block; height: 16px; width: 16px;"></span> pencil icon
will appear over editable areas when the editing for the page is toggled on. Clicking on the icon will overlay a form over your page to edit the values in context.</p>

<h2>Page Inline Editing</h2>
<p>Page inline editing allows you to edit the values of variables used in the page.
A FUEL logo will be displayed in the upper right area of the page that can slide out and provide you 
the ability to toggle inline editing, publish status and caching. Clicking the inline editing pencil will toggle inline editing on.</p>
<img src="<?=img_path('examples/page_inline_editing.jpg', 'user_guide')?>" class="screen" />

<h2>Module Inline Editing</h2>
<p>For those pages that may not be editible, you can still allow for module data to be edited (e.g. news items).
The top right area <strong>will not</strong> have the controls for page publish status, caching or layouts and will look like the following:</p>
<img src="<?=img_path('examples/inline_editing.jpg', 'user_guide')?>" class="screen" />

<p>Clicking the pencil will reveal the form to edit the module information.</p>
<img src="<?=img_path('examples/inline_editing_form.jpg', 'user_guide')?>" class="screen" />