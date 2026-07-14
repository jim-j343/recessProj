module com.example.acesdesktop {
    requires javafx.controls;
    requires javafx.fxml;

    requires java.sql;
    requires java.net.http;
    requires org.xerial.sqlitejdbc;

    requires com.fasterxml.jackson.databind;
    requires com.fasterxml.jackson.core;
    requires com.fasterxml.jackson.annotation;

    requires org.kordamp.bootstrapfx.core;
    requires java.desktop;

    opens forum to javafx.fxml;
    opens forum.ui to javafx.fxml;
    opens forum.controllers to javafx.fxml;
    opens forum.api.dto to com.fasterxml.jackson.databind;
    exports forum;
    exports forum.ui;
    exports forum.models;
}
