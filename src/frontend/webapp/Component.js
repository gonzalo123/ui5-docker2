sap.ui.define([
  "sap/ui/core/UIComponent",
  "sap/ui/Device",
  "gonzalo123/model/models",
  "gonzalo123/model/api"

], function (UIComponent, Device, models, api) {
  "use strict";
  return UIComponent.extend("gonzalo123.Component", {

    metadata: {
      manifest: "json"
    },

    init: function () {
      UIComponent.prototype.init.apply(this, arguments);
      this.getRouter().initialize();
      this.setModel(models.createDeviceModel(), "device");

      // In a real app we should obtain this token from a idp
      api.setToken("superSecretToken");
      api.setBaseUrl("/backend/api");
    }
  });
});
