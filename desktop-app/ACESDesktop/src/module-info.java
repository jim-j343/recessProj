module com.example.acesdesktop {
    requires javafx.controls;
    requires javafx.fxml;

    requires java.sql;
    requires java.net.http;
    requires org.xerial.sqlitejdbc;

    requires org.kordamp.bootstrapfx.core;
    requires java.desktop;

    opens forum to javafx.fxml;
    opens forum.ui to javafx.fxml;
    opens forum.controllers to javafx.fxml;
    exports forum;
    exports forum.ui;
    exports forum.models;
}
