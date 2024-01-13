# -*- coding: utf-8 -*-
# Copyright (c) 2017-18 Richard Hull and contributors
# See LICENSE.rst for details.

from luma.core import rpi_gpio_bridge as GPIO


__all__ = ["rpi_gpio", "spidev"]


def __spidev__(self):  # pragma: no cover
    # spidev cant compile on macOS, so use a similar technique to
    # initialize (mainly so the tests run unhindered)
    import spidev
    return spidev.SpiDev()


def __rpi_gpio__(self):
    return(GPIO.bridge_gpiozero())

def rpi_gpio(Class):
    setattr(Class, __rpi_gpio__.__name__, __rpi_gpio__)
    return Class


def spidev(Class):
    setattr(Class, __spidev__.__name__, __spidev__)
    return Class
