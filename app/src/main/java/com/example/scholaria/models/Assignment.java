package com.example.scholaria.models;

public class Assignment {
    private String name;
    private String dueDate;

    public Assignment(String name, String dueDate) {
        this.name = name;
        this.dueDate = dueDate;
    }

    public String getName() { return name; }
    public String getDueDate() { return dueDate; }
}
