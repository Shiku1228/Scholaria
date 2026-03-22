package com.example.scholaria.models;

public class Subject {
    private String name;
    private String code;
    private String classNumber;

    public Subject(String name, String code, String classNumber) {
        this.name = name;
        this.code = code;
        this.classNumber = classNumber;
    }

    public String getName() { return name; }
    public String getCode() { return code; }
    public String getClassNumber() { return classNumber; }
}
