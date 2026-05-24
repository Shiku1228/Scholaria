package com.example.scholaria.models;

public class Course {
    private final String courseId;
    private final String courseName;
    private final String courseNumber;
    private final String semester;
    private final String schoolYear;
    private final int progress;
    private final int assignmentsTotal;
    private final int assignmentsSubmitted;
    private final String teacherName;
    private final String enrollmentStatus;
    private final int coverImageRes;

    public Course(String title, String subtitle, String semester, String year, int progress, String assignments) {
        this(
            null,
            title,
            subtitle,
            semester,
            year,
            progress,
            parseSubmitted(assignments),
            parseTotal(assignments),
            "Teacher",
            "Enrolled",
            0
        );
    }

    public Course(
        String courseId,
        String courseName,
        String courseNumber,
        String semester,
        String schoolYear,
        int progress,
        int assignmentsSubmitted,
        int assignmentsTotal,
        String teacherName,
        String enrollmentStatus,
        int coverImageRes
    ) {
        this.courseId = courseId;
        this.courseName = courseName;
        this.courseNumber = courseNumber;
        this.semester = semester;
        this.schoolYear = schoolYear;
        this.progress = progress;
        this.assignmentsSubmitted = assignmentsSubmitted;
        this.assignmentsTotal = assignmentsTotal;
        this.teacherName = teacherName;
        this.enrollmentStatus = enrollmentStatus;
        this.coverImageRes = coverImageRes;
    }

    private static int parseSubmitted(String assignments) {
        if (assignments == null) return 0;
        String[] parts = assignments.split("/");
        if (parts.length == 0) return 0;
        try {
            return Integer.parseInt(parts[0].trim());
        } catch (Exception e) {
            return 0;
        }
    }

    private static int parseTotal(String assignments) {
        if (assignments == null) return 0;
        String[] parts = assignments.split("/");
        if (parts.length < 2) return 0;
        String total = parts[1].replaceAll("[^0-9]", "").trim();
        try {
            return Integer.parseInt(total);
        } catch (Exception e) {
            return 0;
        }
    }

    public String getCourseId() { return courseId; }
    public String getCourseName() { return courseName; }
    public String getCourseNumber() { return courseNumber; }
    public String getSemester() { return semester; }
    public String getSchoolYear() { return schoolYear; }
    public int getProgress() { return progress; }
    public int getAssignmentsTotal() { return assignmentsTotal; }
    public int getAssignmentsSubmitted() { return assignmentsSubmitted; }
    public String getTeacherName() { return teacherName; }
    public String getEnrollmentStatus() { return enrollmentStatus; }
    public int getCoverImageRes() { return coverImageRes; }

    public String getTitle() { return courseName; }
    public String getSubtitle() { return courseNumber; }
    public String getYear() { return schoolYear; }
    public String getAssignments() { return assignmentsSubmitted + "/" + assignmentsTotal + " assignments"; }
}
