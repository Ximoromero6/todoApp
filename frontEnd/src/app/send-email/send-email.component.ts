import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-send-email',
  templateUrl: './send-email.component.html',
  styleUrls: ['./send-email.component.scss']
})
export class SendEmailComponent implements OnInit {

  constructor() { }
  token: string

  ngOnInit(): void {
    /* this.token = localStorage.getItem('userToken'); */
  }

}
