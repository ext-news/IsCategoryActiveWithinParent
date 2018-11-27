This is just a PoC of another variant of https://docs.typo3.org/typo3cms/extensions/news/Tutorials/Templates/MultiCategorySelection/Index.html

Given category tree:

* Cat 1
    * Sub a
    * Sub b
    * Sub c
* Cat 2
    * Sub d
    * Sub e
* Cat 3
    * Sub f
    * Sub g

If category `Sub c` & `Sub f` has been selected and the user wants to select `Sub b`, the category `c` is unselected.