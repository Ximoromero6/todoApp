import { Component, OnInit } from '@angular/core';
import { ActivateAcountService } from './activate-acount.service';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-activate-account',
  templateUrl: './activate-account.component.html',
  styleUrls: ['./activate-account.component.scss']
})
export class ActivateAccountComponent implements OnInit {

  constructor(
    private servicio: ActivateAcountService,
    private router: ActivatedRoute,
    private route: Router
  ) { }

  token: string

  ngOnInit(): void {
    this.router.params.subscribe((response) => {
      this.token = response["token"];
      if (this.token != '') {
        this.servicio.activateAccount(this.token).subscribe(
          (response) => {
            console.log(response);
            if (response.status == 1) {
              localStorage.setItem("userData", btoa(response.data));
            } else {
              this.route.navigate(['/home']);
            }

            /* console.log(btoa(response.data));
            console.log(atob(localStorage.getItem('userData'))); */
          },
          (error) => {
            console.log(error);
          }
        )
      } else {
        console.log('No existe token');
      }
    });
  }


}
