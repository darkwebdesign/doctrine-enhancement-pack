# Doctrine Enhancement Pack

[![Latest Stable Version](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/v/stable?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)
[![Total Downloads](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/downloads?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)

[![Build Status](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack.svg?branch=2.5)](https://travis-ci.org/darkwebdesign/doctrine-enhancement-pack?branch=2.5)
[![Coverage Status](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack/branch/2.5/graph/badge.svg)](https://codecov.io/gh/darkwebdesign/doctrine-enhancement-pack)
[![PHP Version](https://img.shields.io/badge/php-5.4%2B-777BB3.svg)](https://php.net/)
[![Doctrine Version](https://img.shields.io/badge/doctrine-2.5-2E6BC8.svg)](http://www.doctrine-project.org/)

Doctrine Enhancement Pack is a collection of Doctrine enhancements that you can use in your Doctrine projects.

Learn more about it in its [documentation](https://github.com/darkwebdesign/doctrine-enhancement-pack/blob/2.5/doc/index.md).

## Features

### Event enhancements

* Access to the original entity in the `pre-update` and `post-update` lifecycle events.
* Access to the created, updated (also their original entities) and deleted entities in the `on-flush` and `post-flush` events.
* Modifying the actual entities (instead of via "change set" array) in the `on-flush` event and `pre-update` lifecycle event.
* Automatic recomputing of the "change set" after modifying the entities in the `on-flush` event.

## Installing via Composer

```bash
composer require darkwebdesign/doctrine-enhancement-pack
```

```bash
composer install
```

## License

Doctrine Enhancement Pack is licensed under the MIT License - see the `LICENSE` file for details.
