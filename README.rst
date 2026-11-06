.. role:: raw-latex(raw)
   :format: latex
..

TYPO3 extension tsparser
========================

What it does
------------

The current TYPO3 Constant Editor does not support enough native field types. This extension hooks into the TypoScript processing pipeline to add custom data types, enabling a more sophisticated and precise generation of backend configuration options.


Installation
------------

Just install the extension. It works seamlessly out-of-the-box with any modern extension configured to support extended types.

This extension operates fully update-safe and compliant with the native TYPO3 v14 Core database storage layout, avoiding any core overrides or legacy hooks.


Reference
----------

Syntax:
~~~~~~~

Custom types are declared directly inside the TypoScript constant configuration comments using the standardized UserFunction syntax:

.. code-block:: typoscript

   # type=user[JambageCom\Tsparser\UserFunction\RenderEintPlus->render]


Constant Editor Types:
~~~~~~~~~~~~~~~~~~~~~~

See the official TYPO3 Constant Editor Documentation <https://typo3.org> for a complete list of native constants types.

New configuration types provided by this extension:

*   **RenderEintPlus** *(formerly eint+)* … empty or integer.

    If nothing has been entered into the backend field, the constant value remains completely empty in the template. Be aware that an empty constant signifies that *nothing is intended* or configured, which behaves technically different from explicitly setting a value to ``0``.

Examples:
"""""""""
To clear a default configuration value (e.g., allowing all products across all categories by leaving the category filter empty), configure the behavior in your extension's ``constants.typoscript`` file:

.. code-block:: typoscript

    # cat=plugin.products//; type=user[JambageCom\Tsparser\UserFunction\RenderEintPlus->render]; label=default category ID: ID of the default category that will be shown in the list view when no tt_products[cat] parameter is given
    defaultCategoryID =
