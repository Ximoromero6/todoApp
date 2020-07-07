import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { LoginService } from './login/login.service';

@Injectable({
  providedIn: 'root'
})
export class GuardianLoginGuard implements CanActivate {

  constructor(
    private router: Router,
    private LoginService: LoginService
  ) { }

  canActivate() {
    let userData;

    try {

      if (localStorage.getItem('userData') != null) {
        userData = JSON.parse(atob(localStorage.getItem('userData')));
      } else {
        userData = JSON.parse(atob(sessionStorage.getItem('userData')));
      }

      if (userData != null) {

        this.LoginService.isLogged(JSON.stringify(userData))
          .subscribe(
            (response) => {
              console.log(`RESPONSE STATUS: ${response.response}`);
              if (response.status === 1) {
                this.router.navigate(['/home']);
                return false;
              }
            },
            (error) => {
              console.log(error);
            }
          );
        return true;
      } else {
        console.log('A');
        return true;
      }

    } catch (e) {
      console.log(e);
      return true;

    }
  }

}
