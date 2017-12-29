# Doctrine Enhancement Pack

[![Latest Stable Version](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/v/stable?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)
[![Total Downloads](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/downloads?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)

[![Build Status](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack.svg?branch=2.4)](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack?branch=2.4)
[![Coverage Status](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack/branch/2.4/graph/badge.svg)](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.3-777BB3.svg)](https://php.net/)
[![Minimum Doctrine Version](https://img.shields.io/badge/doctrine-%3E%3D%202.4-2E6BC8.svg)](http://www.doctrine-project.org/)

Doctrine Enhancement Pack is a collection of Doctrine enhancements that you can use in your Doctrine projects.

Learn more about it in its [documentation](https://github.com/darkwebdesign/doctrine-enhancement-pack/blob/2.4/doc/index.md).

## Features

### Event enhancements

* Access to the original entity in the `pre-update` and `post-update` lifecycle event.
* Modifying via updated entity itself (instead of via "change set" array) in the `pre-update` lifecycle event.
* Automatical recomputing of the "change set" after modifying the updated entity in the `pre-update` lifecycle event.

## Installing via Composer

```bash
composer require darkwebdesign/doctrine-enhancement-pack
```

```bash
composer install
```

## License

Doctrine Enhancement Pack is licensed under the MIT License - see the `LICENSE` file for details.