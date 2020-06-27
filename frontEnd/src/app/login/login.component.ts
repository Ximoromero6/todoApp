import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { LoginService } from './login.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
})
export class LoginComponent implements OnInit {
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;
  formularioLogin: FormGroup;

  constructor(
    private formBuilder: FormBuilder,
    private login: LoginService,
    private ruta: Router
  ) {}

  ngOnInit(): void {
    this.formularioLogin = this.formBuilder.group({
      email: ['', Validators.required],
      clave: ['', Validators.required],
    });
  }

  validarDatosLogin() {
    if (this.formularioLogin.invalid) {
      this.formularioLogin.get('email').markAsDirty();
      this.formularioLogin.get('clave').markAsDirty();
      return;
    }
    console.log(JSON.stringify(this.formularioLogin.value));

    //Función para ver si el usuario está logueado
    this.login
      .validarLogin(
        this.formularioLogin.value.email,
        this.formularioLogin.value.clave
      )
      .subscribe(
        (response) => {
          console.log(response);
          if (response.status == 1) {
            alert('LOGIN CORRECTO');
            //Redireccionar a página verificar cuenta pero si hay un error se muestra
            // localStorage.setItem('userToken', response.data.token);
          } else {
            this.mensajeNotificacion = response.response;
            this.ocultarNotificacion(false);
          }
        },
        (error) => {
          console.log(error);
        }
      );
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }
}
