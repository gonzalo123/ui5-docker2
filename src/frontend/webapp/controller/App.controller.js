sap.ui.define([
  'sap/ui/core/mvc/Controller',
  'sap/ui/model/json/JSONModel',
  'sap/m/MessageToast',
  'gonzalo123/model/api',
], function(Controller, JSONModel, MessageToast, api) {
  'use strict';

  var ioInit = function() {
    jQuery.sap.require('io');

    this.socket = io.connect('ws://:', {
      timeout: 5000,
      'sync disconnect on unload': true,
    });

    this.socket.on('connect_error', function(e) {
      console.log('error connecting to socket.io', e);
    });

    this.socket.on('connect', function() {
      console.log('connected to socket.io');
    });

    this.socket.on('click', function(data) {
      this.model.setProperty('/Data/count', data.count);
    }.bind(this));
  };

  return Controller.extend('gonzalo123.controller.App', {
    model: new JSONModel({
      Data: {count: 0},
    }),

    onInit: function() {
      ioInit.apply(this);

      this.getView().setModel(this.model);
      api.get('/', {}).then(function(data) {
        this.model.setProperty('/Data/count', data.count);
      }.bind(this));
    },

    io: function() {

    },

    itemPressHandle: function() {
      api.post('/', {}).then(function(data) {
        MessageToast.show('Pressed : ' + data.date);
      }.bind(this));
    },

  });
});
