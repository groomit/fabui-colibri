#!/bin/env python
# -*- coding: utf-8; -*-
#
# (c) 2016 FABtotum, http://www.fabtotum.com
#
# This file is part of FABUI.
#
# FABUI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FABUI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with FABUI.  If not, see <http://www.gnu.org/licenses/>.

__authors__ = "Marco Rizzuto, Daniel Kesler"
__license__ = "GPL - https://opensource.org/licenses/GPL-3.0"
__version__ = "1.0"

# Import standard python module
import re

# Import external modules

# Import internal modules
from fabtotum.fabui.macros.common import getEeprom, configure_head, configure_feeder, get_versions
from fabtotum.utils.translation import _, setLanguage

def home_all(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    
    try:
        zprobe_disabled = int(app.config.get('settings', 'zprobe.enable')) == 0
        z_max_offset    = app.config.get('settings', 'z_max_offset')
    except KeyError:
        z_max_offset = 206.0
        zprobe_disabled = False

    app.trace( _("Homing all axes") )
    app.macro("G90", "ok", 2, _("Set abs position"), verbose=False)
    
    if zprobe_disabled :
        app.macro("G27", "ok", 200,                             _("Homing all axes"), verbose=False)
        app.macro('G92 Z{0}'.format(z_max_offset), "ok", 99,    _("Set Z Max"), verbose=False)
        app.macro("G0 Z50 F10000", "ok", 15,                    _("Raising"), verbose=False)
    else:
        app.macro("G28", "ok", 200,                             _("Homing all axes"), verbose=False)

def start_up(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    
    try:
        color = app.config.get('settings', 'color')
    except KeyError:
        color = {
            'r' : 255,
            'g' : 255,
            'b' : 255,
        }
    
    try:
        safety_door = app.config.get('settings', 'safety')['door']
    except KeyError:
        safety_door = 0
    
    try:
        switch = app.config.get('settings', 'switch')
    except KeyError:
        switch = 0
    
    try:
        collision_warning = app.config.get('settings', 'safety')['collision-warning']
    except KeyError:
        collision_warning = 0
    
    app.trace( _("Starting up") )
    app.macro("M728",                   "ok", 2, _("Alive!"), verbose=False)
    app.macro("M402",                   "ok", 1, _("Probe Up") )
    app.macro("M701 S"+str(color['r']), "ok", 2, _("turning on lights"), verbose=False)
    app.macro("M702 S"+str(color['g']), "ok", 2, _("turning on lights"), verbose=False)
    app.macro("M703 S"+str(color['b']), "ok", 2, _("turning on lights"), verbose=False)
    
    app.macro("M732 S"+str(safety_door),"ok", 2, _("Safety Settings"), verbose=False)
    app.macro("M714 S"+str(switch),     "ok", 2, _("Homing direction"), verbose=False)
    

    app.macro("M734 S"+str(collision_warning),  "ok", 2, _("Machine Limits Collision warning"), verbose=False)

def shutdown(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    app.trace( _("Shutting down...") ) 
    app.macro("M300",   "ok", 5, _("Play alert sound!"), verbose=False)
    app.macro("M729",   "ok", 2, _("Asleep!"), verbose=False)
    
def raise_bed(app, args = None, lang='en_US.UTF-8'):
    """
    For homing procedure before probe calibration and print without homing.
    """
    _ = setLanguage(lang)
    try:
        zprobe = app.config.get('settings', 'zprobe')
        zprobe_disabled = (zprobe['disable'] == 1)
        zmax_home_pos   = float(zprobe['zmax'])
    except KeyError:
        zmax_home_pos = 206.0
        zprobe_disabled = False
    
    app.macro("M402",   "ok", 4,    _("Raising probe"), verbose=True)
    app.macro("G90",    "ok", 2,    _("Setting absolute position") )
    
    #macro("G27","ok",100,"Homing all axes",0.1)
    #macro("G0 Z10 F10000","ok",15,"raising",0.1)
    #macro("G28","ok",100,"homing all axes",0.1)
    if zprobe_disabled:
        app.macro("G27 X0 Y0 Z" + str(zmax_home_pos),   "ok", 100,  _("Homing all axes") )
        app.macro("G0 Z50 F10000",                      "ok", 15,   _("Raising") )
    else:
        app.macro("G27",            "ok", 100,  _("Homing all axes") )
        app.macro("G0 Z10 F10000",  "ok", 15,   _("Raising") )
        app.macro("G28",            "ok", 100,  _("Homing all axes"), verbose=False)

def auto_bed_leveling(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    
    app.trace( _("Auto Bed leveling Initialized") )
    app.macro("G28",                "ok", 90,   _("Homing Z axis") )
    app.macro("G28 X Y",            "ok", 90,   _("Homing X/Y axis") )
    app.macro("G29",                "ok", 140,  _("Probing the bed") )
    app.macro("G0 X5 Y5 Z60 F2000", "ok", 100,  _("Getting to idle position") )

def probe_down(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    
    print "LANG:", lang
    
    app.macro("M401",   "ok", 1, _("Probe Down") )
    
def probe_up(app, args = None, lang='en_US.UTF-8'):
    
    print "LANG:", lang
    
    _ = setLanguage(lang)
    app.macro("M402",   "ok", 1, _("Probe Up") )

def safe_zone(app, args = None, lang='en_US.UTF-8'):
    """ .. todo: turn these into macroes """
    _ = setLanguage(lang)
    pass
    #~ app.send("G91")
    #~ app.send("G0 E-5 F1000")
    #~ app.send("G0 Z+1 F1000")
    #~ app.send("G90")
    #~ app.send("G27 Z0")
    #~ app.send("G0 X210 Y210")

def engage_4axis(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    units_a = app.config.get('settings', 'a')
    try:
        feeder_disengage_offset = app.config.get('settings', 'feeder')['disengage_offset']
    except KeyError:
        feeder_disengage_offset = 2
    
    app.trace( _("Engaging 4th Axis") )
    app.macro("G27",                "ok", 100,  _("Zeroing Z axis") )
    app.macro("G91",                "ok", 1,    _("Setting Relative position"), verbose=False)
    app.macro("M120",               "ok", 1,    _("Disable Endstop checking") )
    app.macro("G0 Z+"+str(feeder_disengage_offset)+" F300", "ok", 90,    _("Engaging 4th Axis Motion") )
    #app.macro("M400",               "ok", 5,    _("Waiting for all moves to finish"), verbose=False)
    app.macro("M121",               "ok", 1,    _("Enable Endstop checking") )
    app.macro("M92 E"+str(units_a), "ok", 1,    _("Setting 4th axis mode") )
    app.macro("G92 Z241",           "ok", 1,    _("Setting position"), verbose=False)
    app.macro("G90",                "ok", 1,    _("Setting Absolute position"), verbose=False)
    app.macro("G0 Z234",            "ok", 1,    _("Check position"), verbose=False)
    app.macro("M300",               "ok", 3,    _("Play beep sound"), verbose=False)
    
def do_4th_axis_mode(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    units_a = app.config.get('settings', 'a')
    app.macro("M92 E"+str(units_a), "ok", 1,    _("Setting 4th axis mode"), verbose=False)

def version(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    return get_versions(app, lang)

def set_ambient_color(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    red = args[0]
    green = args[1]
    blue = args[2]
    
    app.macro("M701 S{0}".format(red),   "ok", 1,    _("Setting Red Color"))
    app.macro("M702 S{0}".format(green),   "ok", 1,    _("Setting Green Color"))
    app.macro("M703 S{0}".format(blue),   "ok", 1,    _("Setting Blue Color"))

def install_head(app, args, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    head_name = args[0]
    
    result = configure_head(app, head_name, lang)
    app.trace(_("Restarting totumduino"))
    return result
    
def install_feeder(app, args, lang='en_US.UTF-8'):
    feeder_name = args[0]
    result = configure_feeder(app, feeder_name, lang)
    return result
    
def clear_errors(app, args = None, lang='en_US.UTF-8'):
    app.macro("M999",   "ok", 1,    _("Clearing error state"))
    app.macro("M728",   "ok", 1,    _("Awaken"), verbose=False)

def read_eeprom(app, args = None, lang='en_US.UTF-8'):
    _ = setLanguage(lang)
    return getEeprom(app, lang)
