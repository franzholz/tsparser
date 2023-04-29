.. role:: raw-latex(raw)
   :format: latex
..

TYPO3 extension tsparser
========================

What is does
------------

The current TYPO3 Constant Editor does not support enough types. This extension adds new types in order to have a more sophisticated generation of the template constants.


Installation
------------

Just install the extension together with any other extension which supports this patch. 
This will enable new types for the template constants. This extension is only needed 
if you use the Constant Editor to edit the template constants.
Be aware that this extension is using code which overwrites the TYPO3 Core file
:file:`TYPO3\CMS\Core\TypoScript\ExtendedTemplateService.php`. 
This could lead to a misbehaviour of the TYPO3 Constant Editor in case of an error.

Reference
----------

Syntax:
~~~~~~~

   [type=type;]


Constant Editor Types:
~~~~~~~~~~~~~~~~~~~~~~

See `Constant Editor https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/UsingSetting/TheConstantEditor.html`__ 
chapter 'type' for a complete list of all possible constants types.

new:

*   eint+ … empty or integer.

    If nothing has been entered, then the constants will remain empty. Be aware that an empty 
    constant means that nothing is intended and that this is different to setting it to 0.

Examples:
"""""""""
Make the category empty. This will not use a default category and it will allow all products of all categories.
Configure the behaviour of the Constant Editor in the file constants.txt  of your TYPO3 extension.

    # cat=plugin.products//; type=eint+; label=default category ID: ID of the default category that will be shown in the list view when no tt_products[cat] parameter is given
    defaultCategoryID =

