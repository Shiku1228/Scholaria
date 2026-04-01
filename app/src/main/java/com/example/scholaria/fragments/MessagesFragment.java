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
import com.example.scholaria.adapters.MessageAdapter;
import com.example.scholaria.adapters.SuggestedPersonAdapter;
import com.example.scholaria.models.Message;
import com.example.scholaria.models.SuggestedPerson;
import java.util.ArrayList;
import java.util.List;

public class MessagesFragment extends Fragment {

    private RecyclerView rvMessages, rvSuggestedPeople;
    private MessageAdapter messageAdapter;
    private SuggestedPersonAdapter suggestedAdapter;
    private List<Message> messageList = new ArrayList<>();
    private List<SuggestedPerson> suggestedList = new ArrayList<>();

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        View view = inflater.inflate(R.layout.fragment_messages, container, false);

        rvMessages = view.findViewById(R.id.rvMessages);
        rvSuggestedPeople = view.findViewById(R.id.rvSuggestedPeople);

        initData();
        setupRecyclerViews();

        return view;
    }

    private void initData() {
        // Sample data for suggested people (Horizontal list)
        suggestedList.clear();
        suggestedList.add(new SuggestedPerson("Elena", 0));
        suggestedList.add(new SuggestedPerson("Marcus", 0));
        suggestedList.add(new SuggestedPerson("Sarah", 0));
        suggestedList.add(new SuggestedPerson("John", 0));
        suggestedList.add(new SuggestedPerson("David", 0));
        suggestedList.add(new SuggestedPerson("Maria", 0));

        // Sample data for the messaging interface (Vertical list)
        messageList.clear();
        messageList.add(new Message("Dr. Elena Richards", "Don't forget to submit your research proposal by Friday.", "10:30 AM", 2, 0));
        messageList.add(new Message("Prof. Marcus Thorne", "The lecture slides for Chapter 5 are now available.", "Yesterday", 0, 0));
        messageList.add(new Message("Study Group: CC106", "Does anyone have the notes from today's session?", "Yesterday", 5, 0));
        messageList.add(new Message("System Notification", "Your assignment 'Database Design' has been graded.", "2 days ago", 0, 0));
        messageList.add(new Message("Sarah Jenkins", "Are we still meeting at the library later?", "3 days ago", 1, 0));
    }

    private void setupRecyclerViews() {
        // Vertical Messages
        messageAdapter = new MessageAdapter(messageList);
        rvMessages.setLayoutManager(new LinearLayoutManager(getContext()));
        rvMessages.setAdapter(messageAdapter);

        // Horizontal Suggested People
        suggestedAdapter = new SuggestedPersonAdapter(suggestedList);
        rvSuggestedPeople.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        rvSuggestedPeople.setAdapter(suggestedAdapter);
    }
}
