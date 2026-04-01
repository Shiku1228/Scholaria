package com.example.scholaria.fragments;

import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.scholaria.R;
import com.example.scholaria.adapters.NotificationAdapter;
import com.example.scholaria.models.Notification;
import java.util.ArrayList;
import java.util.List;

public class NotificationsFragment extends Fragment {

    private RecyclerView rvNotifications;
    private NotificationAdapter adapter;
    private List<Notification> notificationList = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_notifications, container, false);

        rvNotifications = view.findViewById(R.id.rvNotifications);

        initData();
        setupRecyclerView();

        return view;
    }

    private void initData() {
        notificationList.clear();
        // Sample data for Notifications
        notificationList.add(new Notification("New Assignment", "You have a new assignment in Computer Programming 2.", "2h ago", "assignment", false));
        notificationList.add(new Notification("Grade Released", "Your grade for 'Database Midterms' has been posted.", "5h ago", "grade", false));
        notificationList.add(new Notification("System Maintenance", "Scholaria will be down for maintenance tonight at 12:00 AM.", "1d ago", "system", true));
        notificationList.add(new Notification("New Assignment", "You have a new assignment in Web Systems.", "2d ago", "assignment", true));
        notificationList.add(new Notification("Grade Released", "Your grade for 'Data Structures Quiz 1' has been posted.", "3d ago", "grade", true));
        notificationList.add(new Notification("Class Cancelled", "The class for CC106 tomorrow is cancelled.", "4d ago", "system", true));
    }

    private void setupRecyclerView() {
        adapter = new NotificationAdapter(notificationList);
        rvNotifications.setLayoutManager(new LinearLayoutManager(getContext()));
        rvNotifications.setAdapter(adapter);
    }
}
