package com.example.scholaria.models;

public class Notification {
    private String title;
    private String description;
    private String time;
    private String type; // e.g., "assignment", "grade", "system"
    private boolean isRead;

    public Notification(String title, String description, String time, String type, boolean isRead) {
        this.title = title;
        this.description = description;
        this.time = time;
        this.type = type;
        this.isRead = isRead;
    }

    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getTime() { return time; }
    public String getType() { return type; }
    public boolean isRead() { return isRead; }
}
