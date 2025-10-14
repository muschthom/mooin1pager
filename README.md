# MOOIN1PAGER course format

**MOOIN** stands for **Mobile Open Online Interactive eNvironment**.

It is used as Moodle course format for One-Pager courses. As a sibling-format of the [mooin-format](https://github.com/ild-thl/moodle-format_mooin) it can be run individually. Nevertheless because the UI/UX is very close to the mooin-MOOC-theme, this course format is meant to be visually aligned with a platform using also the mooin4-theme.   

## Installation
To use the course format, at least two Moodle plugins are necessary.

We need to install the **course format**

    cd /path/to/moodle/course/format/
    
and the **MOOIN 4.x Design**

    cd /path/to/moodle/theme/
    
    git clone -b mooin_405 https://github.com/ild-thl/moodle-theme_mooin.git mooin4

and the **Boost Union Design**

    cd /path/to/moodle/theme/

    git clone -b MOODLE_405_STABLE https://github.com/moodle-an-hochschulen/moodle-theme_boost_union.git boost_union
    
For a better user experience we recommend to use **H5P** (https://moodle.org/plugins/mod_hvp). 

    cd /path/to/moodle/mod/

    git clone https://github.com/h5p/moodle-mod_hvp.git hvp

    cd /path/to/moodle/mod/hvp/

    git submodule update --init

## Usage
First check if changing Designs in courses is enabled. Go to **Site Administration > Appearance > Theme settings** and enable **Allow course themes** (allowcoursethemes).

Then create a new course or navigate to an existing course. In the course settings go to **Course format** and choose **mooin1pager**. Then go to **Appearance > Force theme** and choose **Mooin 4.x**.

## Features 
Features: see https://github.com/muschthom/mooin1pager/wiki

