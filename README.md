# Doctrine Enhancement Pack

[![Latest Stable Version](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/v/stable?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)
[![Total Downloads](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/downloads?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)

[![Build Status](https://github.com/darkwebdesign/doctrine-enhancement-pack/actions/workflows/build.yaml/badge.svg?branch=2.11)](https://github.com/darkwebdesign/doctrine-enhancement-pack/actions/workflows/build.yaml)
[![PHP Version](https://img.shields.io/badge/php-7.2%2B-777BB3.svg)](https://php.net/)
[![Doctrine Version](https://img.shields.io/badge/doctrine-2.11-2E6BC8.svg)](http://www.doctrine-project.org/)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhancement-pack/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhancement-pack)

Doctrine Enhancement Pack is a collection of Doctrine enhancements that you can use in your Doctrine projects.

Learn more about it in its [documentation](https://darkwebdesign.github.io/doctrine-enhancement-pack/docs/2.11).

## Features

### Event enhancements

* Access to the original entity in the `pre-update` and `post-update` lifecycle events.
* Access to the created, updated (also their original entities) and deleted entities in the `on-flush` and `post-flush` events.
* Modifying the actual entities (instead of via "change set" array) in the `on-flush` event and `pre-update` lifecycle event.
* Automatic recomputing of the "change set" after modifying the entities in the `on-flush` event.

## License

Doctrine Enhancement Pack is licensed under the MIT License - see the `LICENSE` file for details.
