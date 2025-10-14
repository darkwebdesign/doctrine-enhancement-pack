# Doctrine Enhanced Events

[![Latest Stable Version](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/v/stable?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)
[![Total Downloads](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/downloads?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)

[![Build Status](https://github.com/darkwebdesign/doctrine-enhanced-events/actions/workflows/build.yaml/badge.svg?branch=2.8)](https://github.com/darkwebdesign/doctrine-enhanced-events/actions/workflows/build.yaml)
[![PHP Version](https://img.shields.io/badge/php-7.2%2B-777BB3.svg)](https://php.net/)
[![Doctrine Version](https://img.shields.io/badge/doctrine-2.8-2E6BC8.svg)](http://www.doctrine-project.org/)
[![License](https://poser.pugx.org/darkwebdesign/doctrine-enhanced-events/license?format=flat)](https://packagist.org/packages/darkwebdesign/doctrine-enhanced-events)

Doctrine Enhanced Events offers enhanced versions of the original Doctrine (lifecycle) events.

Learn more about it in its [documentation](https://darkwebdesign.github.io/doctrine-enhancement-pack/docs/2.8).

## Features

* Access to the original entity in the `pre-update` and `post-update` lifecycle events.
* Access to the created, updated (also their original entities) and deleted entities in the `on-flush` and `post-flush` events.
* Modifying the actual entities (instead of via "change set" array) in the `on-flush` event and `pre-update` lifecycle event.
* Automatic recomputing of the "change set" after modifying the entities in the `on-flush` event.

## License

Doctrine Enhanced Events is licensed under the MIT License - see the `LICENSE` file for details.
